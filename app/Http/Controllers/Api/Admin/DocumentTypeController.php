<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $types = DocumentType::orderBy('name')->get();
        return $this->success($types);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'is_required' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        $type = DocumentType::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'is_required' => $request->is_required,
            'description' => $request->description,
        ]);

        return $this->success($type, 'Tipe dokumen berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $type = DocumentType::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:100',
            'is_required' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['name', 'is_required', 'description']);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $type->update($data);

        return $this->success($type, 'Tipe dokumen berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $type = DocumentType::findOrFail($id);
        $type->delete();

        return $this->success(null, 'Tipe dokumen berhasil dihapus');
    }
}
