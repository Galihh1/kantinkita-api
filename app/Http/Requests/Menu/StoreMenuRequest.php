<?php
namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|min:3|max:200',
            'description'  => 'nullable|string|max:1000',
            'price'        => 'required|numeric|min:0',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_available' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists'   => 'Kategori tidak valid.',
            'name.required'        => 'Nama menu wajib diisi.',
            'name.min'             => 'Nama menu minimal 3 karakter.',
            'price.required'       => 'Harga wajib diisi.',
            'price.numeric'        => 'Harga harus berupa angka.',
            'price.min'            => 'Harga tidak boleh negatif.',
            'photo.image'          => 'File harus berupa gambar.',
            'photo.mimes'          => 'Format gambar: jpg, jpeg, png, webp.',
            'photo.max'            => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
