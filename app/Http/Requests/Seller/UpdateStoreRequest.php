<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->store()->exists() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::trim((string) $this->input('name')),
            'description' => Str::trim((string) $this->input('description')) ?: null,
            'whatsapp' => Str::trim((string) $this->input('whatsapp')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $image = config('lapak.files.store_image');

        return [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(08|62)[0-9]{7,13}$/'],
            'logo' => [
                'nullable',
                'file',
                'mimes:'.implode(',', $image['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$image['max_kb'],
            ],
            'banner' => [
                'nullable',
                'file',
                'mimes:'.implode(',', $image['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$image['max_kb'],
            ],
        ];
    }

    /**
     * @return array{name: string, description: string|null, whatsapp: string}
     */
    public function payload(): array
    {
        return [
            'name' => (string) $this->string('name'),
            'description' => $this->input('description') === null
                ? null
                : (string) $this->input('description'),
            'whatsapp' => (string) $this->string('whatsapp'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 08 atau 62.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama lapak',
            'description' => 'Deskripsi lapak',
            'whatsapp' => 'Nomor WhatsApp',
            'logo' => 'Logo lapak',
            'banner' => 'Banner lapak',
        ];
    }
}
