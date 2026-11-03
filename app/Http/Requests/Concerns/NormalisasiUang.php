<?php

namespace App\Http\Requests\Concerns;

/**
 * Input uang di form diketik dengan pemisah ribuan ("9.965.000").
 * Trait ini mengubahnya menjadi angka murni sebelum divalidasi.
 */
trait NormalisasiUang
{
    protected function uang(mixed $nilai): mixed
    {
        if (! is_string($nilai) || $nilai === '') {
            return $nilai;
        }

        $bersih = str_replace(['Rp', ' ', '.'], '', $nilai);

        return str_replace(',', '.', $bersih);
    }

    /** Angka desimal (qty): terima koma sebagai pemisah desimal. */
    protected function desimal(mixed $nilai): mixed
    {
        return is_string($nilai) ? str_replace(',', '.', trim($nilai)) : $nilai;
    }
}
