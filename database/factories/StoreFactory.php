<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory()->withRole(Role::Seller),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'whatsapp' => '08'.fake()->numerify('##########'),
            'status' => StoreStatus::Closed,
            'is_suspended' => false,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Open,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspended' => true,
        ]);
    }
}
