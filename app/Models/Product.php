<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $store_id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property int $stock
 * @property int $reserved
 * @property int $sold_count
 * @property bool $is_active
 * @property bool $is_suspended
 */
#[Fillable([
    'store_id',
    'category_id',
    'name',
    'description',
    'price',
    'stock',
    'reserved',
    'sold_count',
    'is_active',
    'is_suspended',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Products shown in the public catalog (PRD BR-15, BR-24).
     *
     * @param  Builder<Product>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('is_suspended', false)
            ->whereHas('store', fn (Builder $store) => $store
                ->where('status', StoreStatus::Open)
                ->where('is_suspended', false));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'reserved' => 'integer',
            'sold_count' => 'integer',
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return HasMany<Wishlist, $this>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}
