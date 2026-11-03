<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

/** Validasi Supplier & Klien (struktur kolomnya sama). */
class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! array_key_exists('warna', $this->rules())) {
            return; // Klien tidak punya warna
        }

        // Warna kosong -> pakai warna lama (edit) atau warna preset berikutnya (tambah)
        $warna = is_string($this->warna) ? strtolower(trim($this->warna)) : '';
        $this->merge(['warna' => $warna ?: ($this->route('supplier')?->warna ?? Supplier::warnaBerikut())]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'warna' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_hp.regex' => 'No. HP hanya boleh berisi angka, spasi, +, - atau tanda kurung.',
            'warna.regex' => 'Pilih warna yang valid.',
        ];
    }
}
