<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            return $this->error('Tabel permissions tidak ditemukan di database. Pastikan migrasi telah dijalankan.', 500);
        }

        $permissions = Permission::orderByRaw('"group" ASC')
            ->orderBy('name', 'asc')
            ->get();

        return $this->success($permissions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'group' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        // Auto-generate resource from name (e.g. "Read Menu" -> "Menu")
        $nameParts = explode(' ', $request->name, 2);
        $resource = count($nameParts) > 1 ? ucfirst($nameParts[1]) : ucfirst($nameParts[0]);

        $permission = Permission::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'group'       => $request->group,
            'resource'    => $resource,
            'description' => $request->description,
        ]);

        return $this->success($permission, 'Hak akses berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'group' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['name', 'group', 'description']);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $permission->update($data);

        return $this->success($permission, 'Hak akses berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return $this->success(null, 'Hak akses berhasil dihapus');
    }
}
