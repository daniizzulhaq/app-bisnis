<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\Barang;
use App\Models\Klien;
use App\Models\Supplier;
use App\Services\LaporanService;
use App\Support\OpsiLaporan;
use App\Support\Periode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan per Klien / Supplier / Barang.
 * ?export=pdf  -> lihat PDF di browser (tidak tersimpan otomatis)
 * ?export=pdf&unduh=1 -> simpan file PDF
 * ?export=excel -> unduh Excel
 */
class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporan)
    {
    }

    public function klien(Request $request)
    {
        return $this->laporan($request, 'klien', 'Laporan Per Klien', 'jual',
            fn ($p, $o) => $this->laporan->perKlien($p, $o));
    }

    public function supplier(Request $request)
    {
        return $this->laporan($request, 'supplier', 'Laporan Per Supplier', 'beli',
            fn ($p, $o) => $this->laporan->perSupplier($p, $o));
    }

    public function barang(Request $request)
    {
        return $this->laporan($request, 'barang', 'Laporan Per Barang', 'lengkap',
            fn ($p, $o) => $this->laporan->perBarang($p, $o));
    }

    private function laporan(Request $request, string $jenis, string $judul, string $modeDefault, callable $ambil)
    {
        $periode = Periode::dariRequest($request);
        $opsi = OpsiLaporan::dariRequest($request, $modeDefault);

        $pilihan = [
            'kliens' => Klien::orderBy('nama')->get(['id', 'nama']),
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama', 'warna']),
            'barangs' => Barang::orderBy('nama')->get(['id', 'nama', 'satuan']),
        ];

        // Ringkasan filter untuk judul, mis. "Hotel Mawar, Resto Melati · Supplier: Pak Darto"
        // Bila semua dipilih cukup ditulis "Klien: Semua" agar judul tidak terlalu panjang
        $ringkas = fn ($daftar, array $ids) => $daftar->isNotEmpty() && $daftar->pluck('id')->diff($ids)->isEmpty()
            ? collect(['Semua'])
            : $daftar->whereIn('id', $ids)->pluck('nama');
        $subjek = collect([
            'Klien' => $ringkas($pilihan['kliens'], $opsi->klienIds),
            'Supplier' => $ringkas($pilihan['suppliers'], $opsi->supplierIds),
            'Barang' => $ringkas($pilihan['barangs'], $opsi->barangIds),
        ])->filter(fn ($n) => $n->isNotEmpty())
            ->map(fn ($n, $label) => "{$label}: ".$n->implode(', '))
            ->implode(' · ') ?: null;

        $data = $ambil($periode, $opsi) + $pilihan + compact('periode', 'judul', 'jenis', 'opsi', 'subjek');

        $namaFile = Str::limit(Str::slug($judul.' '.$periode->dariStr().' '.$periode->sampaiStr().' '.($subjek ?? '')), 120, '');

        if ($request->input('export') === 'pdf') {
            $landscape = $opsi->laba() || ($jenis !== 'klien' && $opsi->adaHarga());
            // Lebar isi halaman A4 (dikurangi margin kiri-kanan), untuk membagi lebar kolom tabel
            $pdf = Pdf::loadView('laporan.pdf', $data + ['mode' => 'pdf', 'lebarIsi' => $landscape ? 28.0 : 19.3])
                ->setPaper('a4', $landscape ? 'landscape' : 'portrait');

            return $request->boolean('unduh') ? $pdf->download($namaFile.'.pdf') : $pdf->stream($namaFile.'.pdf');
        }

        if ($request->input('export') === 'excel') {
            return Excel::download(new LaporanExport("laporan.{$jenis}._tabel", $data, $judul), $namaFile.'.xlsx');
        }

        return view('laporan.halaman', $data + ['mode' => 'html']);
    }
}
