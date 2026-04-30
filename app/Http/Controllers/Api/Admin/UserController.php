<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $users = User::when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('username', 'like', "%{$request->search}%")
            )
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->status !== null, fn($q) => $q->where('status', $request->status))
            ->where('is_deleted', 0)
            ->with(['assignedRole', 'tenant.subscription', 'staffTenants.subscription', 'permissions'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|min:3|max:200',
            'username'  => 'required|string|min:3|max:100|unique:users,username|alpha_num',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|min:10|max:20',
            'password'  => 'required|string|min:8',
            'role'      => 'required|in:admin,owner,staff,customer',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = User::create([
            'name'         => $request->full_name,
            'full_name'    => $request->full_name,
            'username'     => $request->username,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'password'     => Hash::make($request->password),
            'role'         => $request->role,
            'company_code' => 'UNIV',
            'created_by'   => $request->user()->username,
            'updated_by'   => $request->user()->username,
        ]);

        if ($request->has('permissions')) {
            $user->permissions()->sync($request->permissions);
        }

        ActivityLog::record('create', "Admin buat user: {$user->email} (role: {$user->role})");
        return $this->success($user, 'User berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::where('is_deleted', 0)->findOrFail($id);

        $request->validate([
            'full_name' => 'sometimes|string|min:3|max:200',
            'phone'     => 'sometimes|string|min:10|max:20',
            'email'     => "sometimes|email|unique:users,email,{$id}",
            'role'      => 'sometimes|in:admin,owner,staff,customer',
            'status'    => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $data = $request->only(['full_name', 'phone', 'email', 'role', 'status']);
        if (isset($data['full_name'])) $data['name'] = $data['full_name'];
        $data['updated_by'] = $request->user()->username;

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('permissions')) {
            $user->permissions()->sync($request->permissions);
        }

        ActivityLog::record('update', "Admin update user: {$user->email}");
        return $this->success($user->fresh(), 'User berhasil diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        if ($id === $request->user()->id) {
            return $this->error('Tidak bisa menghapus akun sendiri.', 422);
        }

        $user = User::where('is_deleted', 0)->findOrFail($id);
        $user->update(['is_deleted' => 1, 'status' => 0, 'updated_by' => $request->user()->username]);

        ActivityLog::record('delete', "Admin hapus user: {$user->email}");
        return $this->success(null, 'User berhasil dihapus');
    }

    public function toggle(Request $request, int $id)
    {
        if ($id === $request->user()->id) {
            return $this->error('Tidak bisa menonaktifkan akun sendiri.', 422);
        }

        $user = User::where('is_deleted', 0)->findOrFail($id);
        $user->update(['status' => !$user->status, 'updated_by' => $request->user()->username]);

        $statusText = $user->status ? 'diaktifkan' : 'disuspensi';
        ActivityLog::record('update', "Admin ubah status user: {$user->email} menjadi {$statusText}");

        return $this->success($user->fresh(), "User berhasil {$statusText}");
    }

    public function impersonate(Request $request, int $id)
    {
        $user = User::where('is_deleted', 0)->where('status', 1)->findOrFail($id);
        
        // Generate token for target user with impersonator metadata in name
        $adminId = $request->user()->id;
        $token = $user->createToken("impersonated_by_{$adminId}")->plainTextToken;

        ActivityLog::record('impersonate', "Admin (@{$request->user()->username}) mulai menyamar sebagai @{$user->username}", $user->id);

        $user->load(['tenant', 'assignedRole']);
        $user->computed_permissions = $user->getAllPermissions()->pluck('slug');

        return $this->success([
            'user'  => $user,
            'token' => $token,
        ], 'Mulai sesi impersonate');
    }
}
