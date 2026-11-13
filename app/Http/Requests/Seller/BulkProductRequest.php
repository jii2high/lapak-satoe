<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->store()->exists() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'action' => 'Aksi',
            'ids' => 'Produk',
        ];
    }
}
