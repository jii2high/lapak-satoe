<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
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
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : 0;

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:'.$images['max_files']],
            'images.*' => [
                'file',
                'mimes:'.implode(',', $images['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$images['max_kb'],
            ],
            'removed_image_ids' => ['nullable', 'array', 'max:'.$images['max_files']],
            'removed_image_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('product_images', 'id')->where('product_id', $productId),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if (! $product instanceof Product) {
                    return;
                }

                $images = $this->file('images');
                $newCount = is_array($images) ? count($images) : 0;
                $removed = $this->input('removed_image_ids');
                $removedIds = is_array($removed)
                    ? array_map(fn ($id) => (int) $id, $removed)
                    : [];

                $remaining = $product->images()
                    ->whereNotIn('id', $removedIds)
                    ->count() + $newCount;

                if ($remaining < 1) {
                    $validator->errors()->add('images', 'Sisakan minimal satu foto produk.');
                }

                $max = (int) config('lapak.files.product_image.max_files');

                if ($remaining > $max) {
                    $validator->errors()->add('images', 'Maksimal '.$max.' foto produk.');
                }
            },
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
            'removed_image_ids' => 'Foto yang dihapus',
        ];
    }
}
