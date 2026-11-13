<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'LS-'.Str::upper(Str::random(8)),
            'buyer_id' => User::factory(),
            'store_id' => Store::factory(),
            'status' => OrderStatus::Unconfirmed,
            'total' => 20000,
            'expires_at' => now()->addHours(24),
            'remind_at' => now()->addHours(12),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
