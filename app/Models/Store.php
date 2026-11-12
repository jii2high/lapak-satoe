<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $whatsapp
 * @property string|null $logo_path
 * @property string|null $banner_path
 * @property StoreStatus $status
 * @property bool $is_suspended
 */
#[Fillable([
    'user_id',
    'name',
    'slug',
    'description',
    'whatsapp',
    'logo_path',
    'banner_path',
    'status',
    'is_suspended',
])]
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    /**
     * Stores shown in the public catalog (PRD BR-13, BR-24).
     *
     * @param  Builder<Store>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('status', StoreStatus::Open)
            ->where('is_suspended', false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'is_suspended' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<StoreNameHistory, $this>
     */
    public function nameHistories(): HasMany
    {
        return $this->hasMany(StoreNameHistory::class);
    }
}
