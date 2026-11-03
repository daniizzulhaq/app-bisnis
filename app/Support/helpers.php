<?php

/*
|--------------------------------------------------------------------------
| Helper global aplikasi Rekap Bisnis
|--------------------------------------------------------------------------
| Di-autoload lewat composer.json ("autoload.files"), sehingga bisa dipakai
| langsung di controller, Blade, PDF, maupun export Excel.
*/

use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('rupiah')) {
    /**
     * Format angka ke Rupiah tanpa desimal. Contoh: 9965000 -> "Rp 9.965.000".
     */
    function rupiah(int|float|string|null $nilai, bool $denganPrefix = true): string
    {
        $nilai = (float) ($nilai ?? 0);
        $teks = number_format(abs($nilai), 0, ',', '.');

        return ($nilai < 0 ? '-' : '').($denganPrefix ? 'Rp '.$teks : $teks);
    }
}

if (! function_exists('angka')) {
    /**
     * Format angka/qty dengan pemisah ribuan. Desimal nol di belakang dibuang.
     * Contoh: 1250.50 -> "1.250,5", 12.00 -> "12".
     */
    function angka(int|float|string|null $nilai, int $desimal = 2): string
    {
        $teks = number_format((float) ($nilai ?? 0), $desimal, ',', '.');

        return $desimal > 0 ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }
}

if (! function_exists('input_uang')) {
    /**
     * Nilai awal untuk <input class="input-uang">: "12000.00" -> "12.000", kosong tetap kosong.
     */
    function input_uang(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return is_string($nilai) ? $nilai : '';
        }

        return number_format(round((float) $nilai), 0, ',', '.');
    }
}

if (! function_exists('input_qty')) {
    /** Nilai awal input qty: "12.50" -> "12.5", "3.00" -> "3". */
    function input_qty(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return is_string($nilai) ? $nilai : '';
        }

        return rtrim(rtrim(number_format((float) $nilai, 2, '.', ''), '0'), '.');
    }
}

if (! function_exists('tanggal')) {
    /**
     * Format tanggal Indonesia. Default: "03 Nov 2026".
     */
    function tanggal(CarbonInterface|string|null $tanggal, string $format = 'd M Y'): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        $tanggal = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal);

        return $tanggal->locale('id')->translatedFormat($format);
    }
}
