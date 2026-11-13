<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'display_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'active_role' => Role::Buyer,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user an extra role, optionally making it active.
     */
    public function withRole(Role $role, bool $active = false): static
    {
        return $this->afterCreating(function (User $user) use ($role, $active) {
            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role' => $role->value,
            ]);

            if ($active) {
                $user->update(['active_role' => $role]);
            }
        });
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role' => Role::Buyer->value,
            ]);
        });
    }
}
