<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function update(User $user, Store $store): bool
    {
        return $store->user_id === $user->id;
    }

    public function toggle(User $user, Store $store): bool
    {
        return $this->update($user, $store);
    }
}
