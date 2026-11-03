<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export Excel generik: merender tabel Blade yang sama dengan halaman laporan
 * (mode "excel" -> angka mentah agar bisa dihitung di Excel).
 */
class LaporanExport implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(
        private string $view,
        private array $data,
        private string $judulSheet = 'Laporan',
    ) {
    }

    public function view(): View
    {
        return view($this->view, $this->data + ['mode' => 'excel']);
    }

    public function title(): string
    {
        return mb_substr($this->judulSheet, 0, 31); // batas nama sheet Excel
    }
}
