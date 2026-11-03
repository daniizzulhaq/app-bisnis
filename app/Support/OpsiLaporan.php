<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Opsi tambahan laporan: filter klien/supplier/barang (bisa lebih dari satu),
 * format qty, dan mode harga yang ditampilkan.
 */
class OpsiLaporan
{
    public const FORMAT_QTY = [
        'satuan' => 'Pakai satuan',
        'angka' => 'Angka saja',
        'desimal' => '2 desimal',
    ];

    public const MODE_HARGA = [
        'lengkap' => 'Lengkap',
        'jual' => 'Jual saja',
        'beli' => 'Beli saja',
    ];

    public function __construct(
        public array $klienIds = [],
        public array $supplierIds = [],
        public array $barangIds = [],
        public string $formatQty = 'satuan',
        public string $modeHarga = 'lengkap',
    ) {
    }

    public static function dariRequest(Request $request, string $modeDefault = 'lengkap'): self
    {
        $mode = $request->input('harga', $modeDefault);
        $format = $request->input('qty', 'satuan');

        return new self(
            self::ids($request->input('klien_id')),
            self::ids($request->input('supplier_id')),
            self::ids($request->input('barang_id')),
            array_key_exists($format, self::FORMAT_QTY) ? $format : 'satuan',
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
        return $this->modeHarga !== 'jual';
    }

    public function jual(): bool
    {
        return $this->modeHarga !== 'beli';
    }

    public function laba(): bool
    {
        return $this->modeHarga === 'lengkap';
    }

    /** Satuan ditampilkan (untuk Excel: kolom terpisah). */
    public function pakaiSatuan(): bool
    {
        return $this->formatQty !== 'angka';
    }

    /**
     * Format qty sesuai opsi. Excel selalu angka mentah agar bisa dihitung.
     */
    public function qty(float|string|null $nilai, ?string $satuan, string $mode): string|float
    {
        $nilai = (float) $nilai;

        if ($mode === 'excel') {
            return round($nilai, 2);
        }

        $teks = $this->formatQty === 'desimal'
            ? number_format($nilai, 2, ',', '.')
            : angka($nilai);

        return $this->pakaiSatuan() && $satuan ? $teks.' '.$satuan : $teks;
    }
}
