<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id || $this->ownsStore($user, $order);
    }

    public function actAsSeller(User $user, Order $order): bool
    {
        return $this->ownsStore($user, $order);
    }

    public function cancelAsBuyer(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id
            && $order->status === OrderStatus::Unconfirmed;
    }

    public function confirmAsBuyer(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id
            && $order->status === OrderStatus::AwaitingBuyer;
    }

    private function ownsStore(User $user, Order $order): bool
    {
        return $user->store()->whereKey($order->store_id)->exists();
    }
}
