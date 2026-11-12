<?php

namespace App\Http\Requests\Account;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwitchRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roles = $this->user()
            ->roles()
            ->pluck('role')
            ->map(fn ($role) => $role instanceof Role ? $role->value : (string) $role)
            ->all();

        return [
            'role' => ['required', Rule::in($roles)],
        ];
    }
}
