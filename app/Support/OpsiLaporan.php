<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Opsi tambahan laporan: filter klien/supplier/barang (bisa lebih dari satu),
 * format qty, dan mode harga yang ditampilkan.
 */
class OpsiLaporan
{
    /**
     * Barang yang sama dipesan beberapa kali di tanggal yang sama (mis. pesanan awal + tambahan):
     *  - total   : dijumlah -> "2 kg"
     *  - rincian : dirinci per pesanan -> "1 kg + 1 kg"
     */
    public const FORMAT_QTY = [
        'total' => 'Total',
        'rincian' => '1 + 1',
    ];

    public const MODE_HARGA = [
        'lengkap' => 'Lengkap',
        'jual' => 'Jual saja',
        'beli' => 'Beli saja',
        'tanpa' => 'Tanpa harga',
    ];

    public function __construct(
        public array $klienIds = [],
        public array $supplierIds = [],
        public array $barangIds = [],
        public string $formatQty = 'total',
        public string $modeHarga = 'lengkap',
    ) {
    }

    public static function dariRequest(Request $request, string $modeDefault = 'lengkap'): self
    {
        $mode = $request->input('harga', $modeDefault);
        $format = $request->input('qty', 'total');

        return new self(
            self::ids($request->input('klien_id')),
            self::ids($request->input('supplier_id')),
            self::ids($request->input('barang_id')),
            array_key_exists($format, self::FORMAT_QTY) ? $format : 'total',
            array_key_exists($mode, self::MODE_HARGA) ? $mode : $modeDefault,
        );
    }

    /** Terima ?klien_id=3 maupun ?klien_id[]=3&klien_id[]=5. */
    private static function ids(mixed $nilai): array
    {
        return collect((array) $nilai)
            ->filter(fn ($v) => is_numeric($v) && (int) $v > 0)
            ->map(fn ($v) => (int) $v)
            ->unique()->values()->all();
    }

    public function beli(): bool
    {
        return in_array($this->modeHarga, ['lengkap', 'beli'], true);
    }

    public function jual(): bool
    {
        return in_array($this->modeHarga, ['lengkap', 'jual'], true);
    }

    /** Mode "Tanpa harga": hanya barang & qty (mis. untuk surat jalan / cek barang). */
    public function adaHarga(): bool
    {
        return $this->modeHarga !== 'tanpa';
    }

    public function laba(): bool
    {
        return $this->modeHarga === 'lengkap';
    }

    public function rinci(): bool
    {
        return $this->formatQty === 'rincian';
    }

    /**
     * Format qty. $pecahan = qty tiap pesanan bila barang dipesan lebih dari sekali di tanggal yang sama.
     * Excel: angka mentah (format Total) agar bisa dihitung; satuan ada di kolom tersendiri.
     */
    public function qty(float|string|null $nilai, ?string $satuan, string $mode, array $pecahan = []): string|float
    {
        $satuan = $mode === 'excel' ? null : $satuan;
        $teks = fn ($v) => angka($v).($satuan ? ' '.$satuan : '');

        if ($this->rinci() && count($pecahan) > 1) {
            return implode(' + ', array_map($teks, $pecahan)); // "1 kg + 1 kg"
        }

        return $mode === 'excel' ? round((float) $nilai, 2) : $teks($nilai);
    }
}
