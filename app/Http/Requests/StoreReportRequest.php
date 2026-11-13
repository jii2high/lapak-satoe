<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'violation_type' => ['required', Rule::in(array_keys(config('lapak.violation_types')))],
            'description' => ['required', 'string', 'max:1000'],
            'product_id' => ['nullable', 'integer'],
            'store_id' => ['nullable', 'integer'],
            'order_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'violation_type.required' => 'Pilih jenis pelanggaran.',
            'violation_type.in' => 'Jenis pelanggaran tidak dikenal.',
            'description.required' => 'Tulis deskripsi masalahnya.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'violation_type' => 'Jenis pelanggaran',
            'description' => 'Deskripsi',
        ];
    }

    /**
     * Objek laporan, produk atau lapak. Divalidasi ulang di ReportService.
     *
     * @return array{product_id: int|null, store_id: int|null, order_id: int|null}
     */
    public function target(): array
    {
        $data = $this->validated();

        return [
            'product_id' => isset($data['product_id']) ? (int) $data['product_id'] : null,
            'store_id' => isset($data['store_id']) ? (int) $data['store_id'] : null,
            'order_id' => isset($data['order_id']) ? (int) $data['order_id'] : null,
        ];
    }
}
