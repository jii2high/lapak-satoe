<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerApplicationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $photos = config('lapak.files.product_photos');
        $card = config('lapak.files.student_card');

        return [
            'full_name' => ['required', 'string', 'max:100'],
            'nis' => ['required', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(08|62)[0-9]{7,13}$/'],
            'store_name' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:1000'],
            'categories' => ['required', 'array', 'min:1', 'max:10'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'product_photos' => ['required', 'array', 'min:1', 'max:'.$photos['max_files']],
            'product_photos.*' => [
                'file',
                'mimes:'.implode(',', $photos['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$photos['max_kb'],
            ],
            'student_card' => [
                'required',
                'file',
                'mimes:'.implode(',', $card['mimes']),
                'mimetypes:image/jpeg,image/png',
                'max:'.$card['max_kb'],
            ],
            'agreed_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 08 atau 62.',
            'categories.required' => 'Pilih minimal satu kategori.',
            'categories.min' => 'Pilih minimal satu kategori.',
            'product_photos.required' => 'Unggah minimal satu foto produk.',
            'product_photos.min' => 'Unggah minimal satu foto produk.',
            'product_photos.max' => 'Maksimal :max foto produk.',
            'student_card.required' => 'Unggah foto kartu pelajar atau kartu perpustakaan.',
            'agreed_terms.accepted' => 'Centang syarat dan ketentuan dulu untuk melanjutkan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'Nama lengkap',
            'nis' => 'NIS',
            'whatsapp' => 'Nomor WhatsApp',
            'store_name' => 'Nama lapak',
            'description' => 'Deskripsi lapak',
            'categories' => 'Kategori',
            'product_photos' => 'Foto produk',
            'student_card' => 'Kartu pelajar',
            'agreed_terms' => 'Syarat dan ketentuan',
        ];
    }
}