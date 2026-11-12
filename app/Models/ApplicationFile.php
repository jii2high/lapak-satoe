<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $application_id
 * @property string $type
 * @property string $path
 * @property Carbon|null $created_at
 */
#[Fillable(['application_id', 'type', 'path'])]
class ApplicationFile extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<SellerApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(SellerApplication::class, 'application_id');
    }
}
