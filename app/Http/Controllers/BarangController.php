<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangRequest;
use App\Models\Barang;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function index(Request $request): View
    {
        // Klasifikasi per supplier: ?supplier=ID, atau ?supplier=tanpa untuk barang tanpa supplier
        $pilih = $request->input('supplier');

        $barangs = Barang::query()
            ->cari($request->q)
            ->when($pilih === 'tanpa', fn ($q) => $q->whereNull('suppliers.id'))
            ->when(is_numeric($pilih), fn ($q) => $q->where('barangs.supplier_id', (int) $pilih))
            ->with('supplier:id,nama,warna')
            ->withCount('transaksiDetails')
            // Urut per supplier (tanpa supplier di akhir), lalu nama barang
            ->leftJoin('suppliers', fn ($j) => $j->on('suppliers.id', '=', 'barangs.supplier_id')->whereNull('suppliers.deleted_at'))
            ->select('barangs.*')
            ->orderByRaw('suppliers.nama IS NULL, suppliers.nama')
            ->orderBy('barangs.nama')
            ->paginate(25)
            ->withQueryString();

        // Jumlah barang per supplier untuk tombol klasifikasi (mengikuti pencarian)
        $jumlah = Barang::query()->cari($request->q)
            ->leftJoin('suppliers', fn ($j) => $j->on('suppliers.id', '=', 'barangs.supplier_id')->whereNull('suppliers.deleted_at'))
            ->selectRaw('suppliers.id AS sid, COUNT(*) AS total')->groupBy('suppliers.id')
            ->pluck('total', 'sid');

        $suppliers = Supplier::orderBy('nama')->get(['id', 'nama', 'warna'])
            ->each(fn ($s) => $s->jumlah_barang = (int) ($jumlah[$s->id] ?? 0));

        return view('barang.index', [
            'barangs' => $barangs,
            'suppliers' => $suppliers,
            'pilih' => $pilih,
            'jumlahSemua' => (int) $jumlah->sum(),
            'jumlahTanpa' => (int) ($jumlah[''] ?? 0),
        ]);
    }

    public function create(): View
    {
        // ?supplier=ID dari halaman klasifikasi -> supplier langsung terpilih
        return view('barang.form', [
            'barang' => new Barang(['satuan' => 'kg', 'supplier_id' => request()->integer('supplier') ?: null]),
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama', 'warna']),
        ]);
    }

    public function store(BarangRequest $request): RedirectResponse
    {
        $barang = Barang::create($request->validated());

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil ditambahkan.");
    }

    public function edit(Barang $barang): View
    {
        return view('barang.form', [
            'barang' => $barang,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama', 'warna']),
        ]);
    }

    public function update(BarangRequest $request, Barang $barang): RedirectResponse
    {
        $barang->update($request->validated());

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil diperbarui.");
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        if ($alasan = $barang->alasanTidakBisaDihapus()) {
            return back()->with('error', $alasan);
        }

        try {
            $barang->delete();
        } catch (QueryException) {
            return back()->with('error', "Barang \"{$barang->nama}\" masih dipakai data lain dan tidak dapat dihapus.");
        }

        return redirect()->route('barang.index')->with('success', "Barang \"{$barang->nama}\" berhasil dihapus. Bisa dipulihkan dari menu Riwayat Hapus.");
    }
}
