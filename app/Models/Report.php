<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reporter_id
 * @property int $reported_user_id
 * @property int|null $product_id
 * @property int|null $store_id
 * @property int|null $order_id
 * @property string $violation_type
 * @property string $description
 * @property ReportStatus $status
 * @property string|null $reporter_note
 * @property string|null $reported_note
 * @property int|null $handled_by
 * @property Carbon|null $handled_at
 * @property Carbon|null $restored_at
 */
#[Fillable([
    'reporter_id',
    'reported_user_id',
    'product_id',
    'store_id',
    'order_id',
    'violation_type',
    'description',
    'status',
    'reporter_note',
    'reported_note',
    'handled_by',
    'handled_at',
    'restored_at',
])]
class Report extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'handled_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
