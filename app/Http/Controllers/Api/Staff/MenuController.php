<?php
namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Models\Menu;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    use ApiResponse;

    private function getTenant(Request $request)
    {
        $user = $request->user();
        
        // If owner, get the tenant they own
        if ($user->role === 'owner') {
            $tenant = $user->tenant;
            if (!$tenant) abort(403, 'Data tenant Anda tidak ditemukan.');
            return $tenant;
        }

        // If staff, get the tenant they are assigned to
        $tenant = $user->staffTenants()->first();
        if (!$tenant) abort(403, 'Staff belum terhubung ke tenant.');
        return $tenant;
    }

    public function index(Request $request)
    {
        $tenant = $this->getTenant($request);

        $menus = Menu::with('category')
            ->where('tenant_id', $tenant->id)->where('is_deleted', 0)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->latest()->paginate(20);

        return $this->success($menus);
    }

    public function store(StoreMenuRequest $request)
    {
        $tenant    = $this->getTenant($request);
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store("menus/{$tenant->id}", 'public')
            : null;

        $menu = Menu::create([
            'tenant_id'    => $tenant->id,
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'photo'        => $photoPath,
            'is_available' => $request->is_available ?? 1,
            'company_code' => 'UNIV',
        ]);

        ActivityLog::record('create', "Tambah menu: {$menu->name}");

        return $this->success($menu->load('category'), 'Menu berhasil ditambahkan', 201);
    }

    public function update(StoreMenuRequest $request, int $id)
    {
        $tenant = $this->getTenant($request);
        $menu   = Menu::where('id', $id)->where('tenant_id', $tenant->id)->where('is_deleted', 0)->firstOrFail();

        $photoPath = $menu->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath) Storage::disk('public')->delete($photoPath);
            $photoPath = $request->file('photo')->store("menus/{$tenant->id}", 'public');
        }

        $menu->update([
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'photo'        => $photoPath,
            'is_available' => $request->is_available ?? $menu->is_available,
        ]);

        ActivityLog::record('update', "Update menu: {$menu->name}");

        return $this->success($menu->fresh('category'), 'Menu berhasil diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        $tenant = $this->getTenant($request);
        $menu   = Menu::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $menu->update(['is_deleted' => 1]);
        ActivityLog::record('delete', "Hapus menu: {$menu->name}");

        return $this->success(null, 'Menu berhasil dihapus');
    }

    public function toggleAvailability(Request $request, int $id)
    {
        $tenant = $this->getTenant($request);
        $menu   = Menu::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $menu->update(['is_available' => !$menu->is_available]);

        $status = $menu->is_available ? 'tersedia' : 'habis';
        ActivityLog::record('update', "Menu {$menu->name} ditandai {$status}");

        return $this->success($menu->fresh(), "Menu ditandai {$status}");
    }

    public function categories(Request $request)
    {
        $tenant = $this->getTenant($request);
        return $this->success(Category::where('tenant_id', $tenant->id)->active()->get());
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $tenant   = $this->getTenant($request);
        $category = Category::create(['tenant_id' => $tenant->id, 'name' => $request->name, 'company_code' => 'UNIV']);

        return $this->success($category, 'Kategori berhasil ditambahkan', 201);
    }

    public function updateCategory(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $tenant   = $this->getTenant($request);
        $category = Category::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $category->update(['name' => $request->name]);

        return $this->success($category, 'Kategori berhasil diperbarui');
    }

    public function destroyCategory(Request $request, int $id)
    {
        $tenant   = $this->getTenant($request);
        $category = Category::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        
        // Soft delete or hard delete? Let's do hard delete since menus handle nullOnDelete
        $category->delete();
        ActivityLog::record('delete', "Hapus kategori: {$category->name}");

        return $this->success(null, 'Kategori berhasil dihapus');
    }
}
