<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisasiUang;
use Illuminate\Foundation\Http\FormRequest;

class TransaksiRequest extends FormRequest
{
    use NormalisasiUang;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Buang baris yang benar-benar kosong, lalu normalisasi angka
        $items = collect($this->input('items', []))
            ->filter(fn ($i) => is_array($i) && array_filter($i, fn ($v) => $v !== null && $v !== ''))
            ->map(fn ($i) => [
                'barang_id' => $i['barang_id'] ?? null,
                'supplier_id' => $i['supplier_id'] ?? null,
                'qty' => $this->desimal($i['qty'] ?? null),
                'harga_beli' => $this->uang($i['harga_beli'] ?? null),
                'harga_jual' => $this->uang($i['harga_jual'] ?? null),
            ])
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'klien_id' => ['required', 'integer', 'exists:kliens,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'items.*.supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.harga_jual' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item barang.',
            'items.min' => 'Minimal harus ada 1 item barang.',
            'items.*.barang_id.required' => 'Pilih barang.',
            'items.*.supplier_id.required' => 'Pilih supplier.',
            'items.*.qty.required' => 'Qty wajib diisi.',
            'items.*.qty.gt' => 'Qty harus lebih dari 0.',
            'items.*.qty.numeric' => 'Qty harus angka.',
            'items.*.harga_beli.required' => 'Harga beli wajib diisi.',
            'items.*.harga_jual.required' => 'Harga jual wajib diisi.',
            'items.*.harga_beli.numeric' => 'Harga beli harus angka.',
            'items.*.harga_jual.numeric' => 'Harga jual harus angka.',
        ];
    }
}
