<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('lapak.admin.email');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'display_name' => (string) config('lapak.admin.name'),
                'username' => (string) config('lapak.admin.username'),
                'password' => (string) config('lapak.admin.password'),
                'active_role' => Role::Admin,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        UserRole::firstOrCreate([
            'user_id' => $admin->id,
            'role' => Role::Admin->value,
        ]);

        UserRole::firstOrCreate([
            'user_id' => $admin->id,
            'role' => Role::Buyer->value,
        ]);
    }
}
