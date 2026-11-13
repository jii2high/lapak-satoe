<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $buyer_id
 * @property int $store_id
 * @property OrderStatus $status
 * @property int $total
 * @property string|null $cancelled_by
 * @property string|null $cancel_note
 * @property Carbon $expires_at
 * @property Carbon|null $remind_at
 * @property Carbon|null $reminded_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $seller_done_at
 * @property Carbon|null $auto_confirm_at
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'code',
    'buyer_id',
    'store_id',
    'status',
    'total',
    'cancelled_by',
    'cancel_note',
    'expires_at',
    'remind_at',
    'reminded_at',
    'confirmed_at',
    'seller_done_at',
    'auto_confirm_at',
    'completed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
            'expires_at' => 'datetime',
            'remind_at' => 'datetime',
            'reminded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'seller_done_at' => 'datetime',
            'auto_confirm_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
