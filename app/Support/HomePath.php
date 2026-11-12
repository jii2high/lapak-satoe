<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

class HomePath
{
    public static function for(User $user): string
    {
        return match ($user->active_role) {
            Role::Seller => '/seller/dashboard',
            Role::Admin => '/admin/dashboard',
            default => '/',
        };
    }
}
