<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;

class CatalogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function productCard(Product $product): array
    {
        $image = $product->relationLoaded('images') ? $product->images->first() : null;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock_available' => max(0, $product->stock - $product->reserved),
            'image_url' => $image === null ? null : Storage::disk('public')->url($image->path),
            'store' => [
                'id' => $product->store->id,
                'name' => $product->store->name,
                'slug' => $product->store->slug,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function productDetail(Product $product): array
    {
        return [
            ...self::productCard($product),
            'description' => $product->description,
            'category' => [
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ],
            'images' => $product->images
                ->map(fn ($image) => Storage::disk('public')->url($image->path))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function storeCard(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo_url' => $store->logo_path === null
                ? null
                : Storage::disk('public')->url($store->logo_path),
            'banner_url' => $store->banner_path === null
                ? null
                : Storage::disk('public')->url($store->banner_path),
            'status' => $store->status->value,
            'is_suspended' => $store->is_suspended,
            'product_count' => (int) ($store->products_count ?? 0),
        ];
    }
}
