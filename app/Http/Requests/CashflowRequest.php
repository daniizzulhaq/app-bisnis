<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisasiUang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi cashflow manual (biaya operasional, modal, dll). */
class CashflowRequest extends FormRequest
{
    use NormalisasiUang;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nominal' => $this->uang($this->nominal)]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'tipe' => ['required', Rule::in(['masuk', 'keluar'])],
            'kategori' => ['required', 'string', 'max:50'],
            'nominal' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
