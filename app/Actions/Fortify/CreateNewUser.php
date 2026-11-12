<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['username'] = Str::lower($input['username']);
        $input['email'] = Str::lower($input['email']);

        Validator::make($input, [
            'username' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'display_name' => $input['username'],
                'username' => $input['username'],
                'email' => $input['email'],
                'password' => $input['password'],
                'active_role' => Role::Buyer,
                'is_active' => true,
            ]);

            UserRole::create([
                'user_id' => $user->id,
                'role' => Role::Buyer->value,
            ]);

            return $user;
        });
    }
}
