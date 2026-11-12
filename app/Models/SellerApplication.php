<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $nis
 * @property string $whatsapp
 * @property string $store_name
 * @property string $description
 * @property ApplicationStatus $status
 * @property string|null $reject_note
 * @property Carbon $agreed_terms_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 */
#[Fillable([
    'user_id',
    'full_name',
    'nis',
    'whatsapp',
    'store_name',
    'description',
    'status',
    'reject_note',
    'agreed_terms_at',
    'reviewed_by',
    'reviewed_at',
])]
class SellerApplication extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'agreed_terms_at' => 'datetime',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'application_categories', 'application_id', 'category_id');
    }

    /**
     * @return HasMany<ApplicationFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class, 'application_id');
    }
}
