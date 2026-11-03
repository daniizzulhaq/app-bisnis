<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisasiUang;
use Illuminate\Foundation\Http\FormRequest;

class BarangRequest extends FormRequest
{
    use NormalisasiUang;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'harga_beli_default' => $this->uang($this->harga_beli_default),
            'harga_jual_default' => $this->uang($this->harga_jual_default),
            'satuan' => is_string($this->satuan) ? strtolower(trim($this->satuan)) : $this->satuan,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_beli_default' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'harga_jual_default' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }
}
