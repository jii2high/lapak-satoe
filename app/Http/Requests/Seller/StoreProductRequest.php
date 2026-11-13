<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->store()->exists() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::trim((string) $this->input('name')),
            'description' => Str::trim((string) $this->input('description')) ?: null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $images = config('lapak.files.product_image');

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['boolean'],
            'images' => ['required', 'array', 'min:1', 'max:'.$images['max_files']],
            'images.*' => [
                'file',
                'mimes:'.implode(',', $images['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$images['max_kb'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Unggah minimal satu foto produk.',
            'images.min' => 'Unggah minimal satu foto produk.',
            'images.max' => 'Maksimal :max foto produk.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama produk',
            'description' => 'Deskripsi produk',
            'category_id' => 'Kategori',
            'price' => 'Harga',
            'stock' => 'Stok',
            'images' => 'Foto produk',
        ];
    }
}
