<?php
namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $staff = $tenant->staff()
            ->where('is_deleted', 0)
            ->when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            )->paginate(20);

        return $this->success($staff);
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|min:3|max:200',
            'username'  => 'required|string|min:3|max:100|unique:users,username|alpha_num',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|min:10|max:20',
            'password'  => 'required|string|min:8',
        ]);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $staff = User::create([
            'name'         => $request->full_name,
            'full_name'    => $request->full_name,
            'username'     => $request->username,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'password'     => Hash::make($request->password),
            'role'         => 'staff',
            'company_code' => 'UNIV',
            'created_by'   => $request->user()->username,
            'updated_by'   => $request->user()->username,
        ]);

        $tenant->staff()->attach($staff->id);
        ActivityLog::record('create', "Tambah staff baru: {$staff->full_name} untuk {$tenant->tenant_name}");

        return $this->success($staff, 'Staff berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'full_name' => 'required|string|min:3|max:200',
            'phone'     => 'required|string|min:10|max:20',
            'email'     => "required|email|unique:users,email,{$id}",
        ]);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        // Ensure staff belongs to this tenant
        $staffQuery = $tenant->staff()->where('users.id', $id);
        if (!$staffQuery->exists()) {
            return $this->error('Staff tidak ditemukan di tenant ini.', 404);
        }

        $staff = User::findOrFail($id);
        $staff->update([
            'full_name'  => $request->full_name,
            'name'       => $request->full_name,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'updated_by' => $request->user()->username,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $staff->update(['password' => Hash::make($request->password)]);
        }

        ActivityLog::record('update', "Update staff: {$staff->full_name}");
        return $this->success($staff->fresh(), 'Staff berhasil diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $staffQuery = $tenant->staff()->where('users.id', $id);
        if (!$staffQuery->exists()) {
            return $this->error('Staff tidak ditemukan di tenant ini.', 404);
        }

        $staff = User::findOrFail($id);
        $tenant->staff()->detach($id);
        $staff->update(['is_deleted' => 1, 'updated_by' => $request->user()->username]);

        ActivityLog::record('delete', "Hapus staff: {$staff->full_name} dari {$tenant->tenant_name}");
        return $this->success(null, 'Staff berhasil dihapus');
    }

    public function toggle(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $staffQuery = $tenant->staff()->where('users.id', $id);
        if (!$staffQuery->exists()) {
            return $this->error('Staff tidak ditemukan di tenant ini.', 404);
        }

        $staff = User::findOrFail($id);
        $staff->update(['status' => !$staff->status, 'updated_by' => $request->user()->username]);

        $statusText = $staff->status ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLog::record('update', "Staff {$statusText}: {$staff->full_name}");
        return $this->success($staff->fresh(), "Staff berhasil {$statusText}");
    }
}
