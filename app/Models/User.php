<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $display_name
 * @property string $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $active_role
 * @property bool $is_active
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['display_name', 'username', 'email', 'password', 'active_role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles()->where('role', $role->value)->exists();
    }

    /**
     * Every application the user ever sent, newest last.
     *
     * @return HasMany<SellerApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(SellerApplication::class);
    }

    /**
     * The application the user is currently waiting on or last heard about.
     *
     * @return HasOne<SellerApplication, $this>
     */
    public function sellerApplication(): HasOne
    {
        return $this->hasOne(SellerApplication::class)->latestOfMany();
    }

    /**
     * @return HasMany<EmailOtp, $this>
     */
    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class);
    }

    /**
     * @return HasOne<Store, $this>
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    /**
     * @return HasOne<Cart, $this>
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * @return HasMany<Wishlist, $this>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }
}
