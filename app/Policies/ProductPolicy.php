<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->store()->exists();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->store()->whereKey($product->store_id)->exists();
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
