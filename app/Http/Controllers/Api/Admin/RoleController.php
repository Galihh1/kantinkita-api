<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        return $this->success($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $role = Role::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return $this->success($role, 'Role berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['name', 'description']);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $role->update($data);

        return $this->success($role, 'Role berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deleting core roles
        if (in_array($role->slug, ['admin', 'owner', 'staff', 'customer'])) {
            return $this->error('Role sistem tidak dapat dihapus', 403);
        }

        $role->delete();
        return $this->success(null, 'Role berhasil dihapus');
    }

    /**
     * Sync permissions to role
     */
    public function syncPermissions(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);

        return $this->success($role->load('permissions'), 'Hak akses role berhasil diperbarui');
    }
}
