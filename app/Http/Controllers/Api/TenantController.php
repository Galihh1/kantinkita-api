<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $tenants = Tenant::with('subscription')
            ->where('status', 1)->where('is_deleted', 0)
            // Sembunyikan tenant milik administrator sistem
            ->whereDoesntHave('owner', function($q) {
                $q->where('role', 'admin');
            })
            ->when($request->search, fn($q) => $q->where('tenant_name', 'like', "%{$request->search}%"))
            ->when($request->is_open, fn($q) => $q->where('is_open', $request->is_open))
            ->latest()->paginate(12);

        return $this->success($tenants);
    }

    public function show(int $id)
    {
        $tenant = Tenant::with(['categories.menus', 'subscription'])
            ->where('status', 1)->where('is_deleted', 0)
            ->whereDoesntHave('owner', function($q) {
                $q->where('role', 'admin');
            })
            ->findOrFail($id);

        return $this->success($tenant);
    }

    public function menus(Request $request, int $id)
    {
        $tenant = Tenant::where('status', 1)->where('is_deleted', 0)->findOrFail($id);

        $menus = $tenant->menus()->with('category')
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->get();

        return $this->success($menus);
    }

    public function myTenant(Request $request)
    {
        $tenant = $request->user()->role === 'owner' 
            ? $request->user()->tenant 
            : $request->user()->staffTenants()->first();

        if (!$tenant) {
            return $this->error('Tenant tidak ditemukan.', 404);
        }

        return $this->success($tenant->load('subscription'));
    }

    public function updateMyTenant(Request $request)
    {
        $tenant = $request->user()->role === 'owner' 
            ? $request->user()->tenant 
            : $request->user()->staffTenants()->first();

        if (!$tenant) {
            return $this->error('Tenant tidak ditemukan.', 404);
        }

        $request->validate([
            'tenant_name' => 'required|string|max:200|unique:tenants,tenant_name,' . $tenant->id,
            'description' => 'nullable|string',
            'address'     => 'nullable|string',
            'phone'       => 'nullable|string|max:20',
            'min_order'   => 'nullable|numeric|min:0',
            'is_open'     => 'nullable|boolean',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->only(['tenant_name', 'description', 'address', 'phone', 'min_order', 'is_open']);

        if ($request->hasFile('photo')) {
            if ($tenant->photo && !\filter_var($tenant->photo, FILTER_VALIDATE_URL)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tenant->photo);
            }
            $data['photo'] = $request->file('photo')->store('tenants/photos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($tenant->banner && !\filter_var($tenant->banner, FILTER_VALIDATE_URL)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tenant->banner);
            }
            $data['banner'] = $request->file('banner')->store('tenants/banners', 'public');
        }

        $data['updated_by'] = $request->user()->username;
        $tenant->update($data);

        return $this->success($tenant->fresh(), 'Profil tenant berhasil diperbarui');
    }
}
