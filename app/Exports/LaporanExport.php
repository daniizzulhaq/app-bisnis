<?php

namespace App\Exports;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel generik: merender tabel Blade yang sama dengan halaman laporan
 * (mode "excel" -> angka mentah agar bisa dihitung di Excel).
 */
class LaporanExport implements FromView, WithStyles, WithTitle
{
    /** Gaya setiap sel tabel laporan: tulisan & garis tebal, rata kiri, tinggi baris 0,75 cm. */
    private const GAYA_SEL = 'border: 1px medium #000000; font-weight: bold; text-align: left; vertical-align: center; word-wrap: break-word; height: 0.75cm';

    public function __construct(
        private string $view,
        private array $data,
        private string $judulSheet = 'Laporan',
    ) {}

    public function view(): View
    {
        $view = view($this->view, $this->data + ['mode' => 'excel']);

        // Maatwebsite hanya memanggil render(); HTML-nya diberi gaya sel lebih dulu.
        return new class($view, fn (string $html) => $this->beriGaya($html)) implements View
        {
            public function __construct(private View $asli, private \Closure $olah) {}

            public function render()
            {
                return ($this->olah)($this->asli->render());
            }

            public function name()
            {
                return $this->asli->name();
            }

            public function with($key, $value = null)
            {
                $this->asli->with($key, $value);

                return $this;
            }

            public function getData()
            {
                return $this->asli->getData();
            }
        };
    }

    /** Tambahkan GAYA_SEL ke setiap <th>/<td> di tabel ber-class "laporan-table". */
    private function beriGaya(string $html): string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><html><body>'.$html.'</body></html>');
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $tabelLaporan = '//table[contains(concat(" ", normalize-space(@class), " "), " laporan-table ")]';

        $sel = $xpath->query($tabelLaporan.'//*[self::th or self::td]');
        foreach ($sel as $el) {
            /** @var DOMElement $el */
            $el->removeAttribute('align');
            $gaya = trim($el->getAttribute('style'), " ;");
            $el->setAttribute('style', ($gaya !== '' ? $gaya.'; ' : '').self::GAYA_SEL);
        }

        // Judul (nama klien/supplier) dipindah ke baris pertama tabelnya, karena Excel
        // menyisipkan baris kosong di antara dua tabel HTML.
        foreach ($xpath->query($tabelLaporan) as $tabel) {
            $judul = $tabel->previousSibling;
            while ($judul && ! $judul instanceof DOMElement) {
                $judul = $judul->previousSibling;
            }
            if (! $judul || $judul->getAttribute('class') !== 'judul-xl') {
                continue;
            }
            $baris = $judul->getElementsByTagName('tr')->item(0);
            $tujuan = $tabel->getElementsByTagName('thead')->item(0) ?? $tabel;
            $tujuan->insertBefore($baris, $tujuan->firstChild);
            $judul->parentNode->removeChild($judul);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $hasil = '';
        foreach ($body->childNodes as $node) {
            $hasil .= $dom->saveHTML($node);
        }

        return $hasil;
    }

    public function title(): string
    {
        return mb_substr($this->judulSheet, 0, 31); // batas nama sheet Excel
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getDefaultColumnDimension()->setWidth(22);
        $sheet->getColumnDimension('A')->setWidth(1, 'cm');   // No
        $sheet->getColumnDimension('B')->setWidth(3.5, 'cm'); // Tanggal (rincian)
        $sheet->getDefaultRowDimension()->setRowHeight(0.75, 'cm');

        // Judul di luar tabel tidak di-wrap agar teksnya tidak terpotong di kolom No yang sempit
        return [
            $sheet->calculateWorksheetDimension() => [
                'alignment' => ['vertical' => 'center'],
            ],
        ];
    }
}
