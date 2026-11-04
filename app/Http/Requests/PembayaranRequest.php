<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisasiUang;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Services\TagihanSupplierService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PembayaranRequest extends FormRequest
{
    use NormalisasiUang;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $jenis = $this->input('jenis');
        $pilihanNota = $this->input('transaksi_id');
        $perTanggal = $jenis === 'supplier' && is_string($pilihanNota) && str_starts_with($pilihanNota, 'tanggal:');

        // Kosongkan pihak yang tidak relevan dengan jenis pembayaran
        $this->merge([
            'jumlah' => $this->uang($this->jumlah),
            'klien_id' => $jenis === 'klien' ? $this->klien_id : null,
            'supplier_id' => $jenis === 'supplier' ? $this->supplier_id : null,
            'transaksi_id' => $perTanggal ? null : ($pilihanNota ?: null),
            'tanggal_nota' => $jenis === 'supplier' ? ($perTanggal ? substr($pilihanNota, 8) : $this->input('tanggal_nota')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in(array_keys(Pembayaran::JENIS))],
            'klien_id' => ['nullable', 'required_if:jenis,klien', 'integer', 'exists:kliens,id,deleted_at,NULL'],
            'supplier_id' => ['nullable', 'required_if:jenis,supplier', 'integer', 'exists:suppliers,id,deleted_at,NULL'],
            'transaksi_id' => ['nullable', 'integer', 'exists:transaksis,id,deleted_at,NULL'],
            'tanggal_nota' => ['nullable', 'date_format:Y-m-d', 'prohibits:transaksi_id'],
            'jumlah' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'metode' => ['required', Rule::in(array_keys(Pembayaran::METODE))],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'klien_id.required_if' => 'Pilih klien yang membayar.',
            'supplier_id.required_if' => 'Pilih supplier yang dibayar.',
        ];
    }

    /**
     * Validasi lanjutan: nota harus milik klien/supplier yang dipilih,
     * dan pembayaran klien tidak boleh melebihi sisa tagihan nota.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->jenis === 'supplier' && $this->tanggal_nota) {
                    $tagihan = app(TagihanSupplierService::class)->perTanggal((int) $this->supplier_id, $this->route('pembayaran')?->id)
                        ->firstWhere('tanggal', $this->tanggal_nota);
                    if (! $tagihan) {
                        $validator->errors()->add('transaksi_id', 'Tidak ada nota beli untuk supplier pada tanggal ini.');
                    } elseif ((float) $this->jumlah > $tagihan->sisa + 0.009) {
                        $validator->errors()->add('jumlah', 'Jumlah melebihi sisa total beli tanggal ini ('.rupiah($tagihan->sisa).').');
                    }

                    return;
                }

                if (! $this->transaksi_id) {
                    return;
                }

                $nota = Transaksi::find($this->transaksi_id);

                if ($this->jenis === 'klien') {
                    if ((int) $nota->klien_id !== (int) $this->klien_id) {
                        $validator->errors()->add('transaksi_id', 'Nota ini bukan milik klien yang dipilih.');

                        return;
                    }

                    // Saat edit, pembayaran ini sendiri tidak dihitung sebagai "sudah dibayar"
                    $abaikanId = $this->route('pembayaran')?->id;
                    $dibayar = (float) $nota->pembayaranKlien()
                        ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
                        ->sum('jumlah');
                    $sisa = (float) $nota->total_jual - $dibayar;

                    if ((float) $this->jumlah > $sisa + 0.009) {
                        $validator->errors()->add('jumlah', 'Jumlah melebihi sisa tagihan nota ('.rupiah(max(0, $sisa)).').');
                    }
                }

                if ($this->jenis === 'supplier'
                    && ! $nota->details()->where('supplier_id', $this->supplier_id)->exists()) {
                    $validator->errors()->add('transaksi_id', 'Nota ini tidak berisi barang dari supplier yang dipilih.');
                }
            },
        ];
    }
}
