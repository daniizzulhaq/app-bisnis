<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\Barang;
use App\Models\Klien;
use App\Models\Supplier;
use App\Services\LaporanService;
use App\Support\Periode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan per Klien / Supplier / Barang.
 * Tambahkan ?export=pdf atau ?export=excel untuk mengunduh.
 */
class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporan)
    {
    }

    public function klien(Request $request)
    {
        $periode = Periode::dariRequest($request);
        $data = $this->laporan->perKlien($periode, $request->integer('klien_id') ?: null);

        return $this->tampilkan($request, 'klien', 'Laporan Per Klien', $periode, $data, [
            'pilihan' => Klien::orderBy('nama')->get(['id', 'nama']),
            'pilihanNama' => 'klien_id',
            'pilihanLabel' => 'Klien',
            'subjek' => $data['klien']?->nama,
        ]);
    }

    public function supplier(Request $request)
    {
        $periode = Periode::dariRequest($request);
        $data = $this->laporan->perSupplier($periode, $request->integer('supplier_id') ?: null);

        return $this->tampilkan($request, 'supplier', 'Laporan Per Supplier', $periode, $data, [
            'pilihan' => Supplier::orderBy('nama')->get(['id', 'nama']),
            'pilihanNama' => 'supplier_id',
            'pilihanLabel' => 'Supplier',
            'subjek' => $data['supplier']?->nama,
        ]);
    }

    public function barang(Request $request)
    {
        $periode = Periode::dariRequest($request);
        $barangId = $request->integer('barang_id') ?: null;
        $data = $this->laporan->perBarang($periode, $barangId);

        return $this->tampilkan($request, 'barang', 'Laporan Per Barang', $periode, $data, [
            'pilihan' => Barang::orderBy('nama')->get(['id', 'nama']),
            'pilihanNama' => 'barang_id',
            'pilihanLabel' => 'Barang',
            'subjek' => $barangId ? Barang::find($barangId)?->nama : null,
        ]);
    }

    /** Render HTML, PDF, atau Excel dari data yang sama. */
    private function tampilkan(Request $request, string $jenis, string $judul, Periode $periode, array $data, array $extra)
    {
        $data = $data + $extra + compact('periode', 'judul', 'jenis');
        $namaFile = Str::slug($judul.' '.$periode->dariStr().' '.$periode->sampaiStr().' '.($extra['subjek'] ?? ''));

        return match ($request->input('export')) {
            'pdf' => Pdf::loadView('laporan.pdf', $data + ['mode' => 'pdf'])
                ->setPaper('a4', $jenis === 'klien' ? 'portrait' : 'landscape')
                ->download($namaFile.'.pdf'),
            'excel' => Excel::download(new LaporanExport("laporan.{$jenis}._tabel", $data, $judul), $namaFile.'.xlsx'),
            default => view('laporan.halaman', $data + ['mode' => 'html']),
        };
    }
}
