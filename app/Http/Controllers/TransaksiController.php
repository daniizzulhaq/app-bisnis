<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransaksiRequest;
use App\Models\Barang;
use App\Models\Klien;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menu "Rekapan": daftar nota penjualan beserta form multi-item.
 */
class TransaksiController extends Controller
{
    public function __construct(private TransaksiService $service)
    {
    }

    public function index(Request $request): View
    {
        $filter = $request->only(['dari', 'sampai', 'klien_id', 'supplier_id', 'barang_id', 'status', 'q']);

        $transaksis = Transaksi::query()
            ->filter($filter)
            ->with('klien:id,nama')
            ->withCount('details')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Ringkasan untuk seluruh hasil filter (bukan hanya halaman ini)
        $ringkasan = Transaksi::query()->filter($filter)
            ->selectRaw('COUNT(*) AS nota, COALESCE(SUM(total_qty),0) AS qty, COALESCE(SUM(total_beli),0) AS beli,
                COALESCE(SUM(total_jual),0) AS jual, COALESCE(SUM(laba),0) AS laba')
            ->first();

        return view('rekapan.index', [
            'transaksis' => $transaksis,
            'ringkasan' => $ringkasan,
            'filter' => $filter,
            ...$this->dataPilihan(),
        ]);
    }

    public function create(): View
    {
        return view('rekapan.form', [
            'transaksi' => new Transaksi(['tanggal' => now()]),
            'items' => [],
            ...$this->dataPilihan(),
        ]);
    }

    public function store(TransaksiRequest $request): RedirectResponse
    {
        $transaksi = $this->service->simpan($request->validated());

        return redirect()->route('rekapan.show', $transaksi)
            ->with('success', "Nota {$transaksi->no_nota} berhasil disimpan.");
    }

    public function show(Transaksi $transaksi): View
    {
        $transaksi->load([
            'klien',
            'details.barang:id,nama,satuan',
            'details.supplier:id,nama',
            'pembayarans' => fn ($q) => $q->with(['klien:id,nama', 'supplier:id,nama'])->orderBy('tanggal'),
        ]);

        $dibayar = (float) $transaksi->pembayarans->where('jenis', 'klien')->sum('jumlah');

        return view('rekapan.show', [
            'transaksi' => $transaksi,
            'dibayar' => $dibayar,
            'sisa' => max(0, (float) $transaksi->total_jual - $dibayar),
        ]);
    }

    public function edit(Transaksi $transaksi): View
    {
        $transaksi->load('details');

        return view('rekapan.form', [
            'transaksi' => $transaksi,
            'items' => $transaksi->details->map->only(['barang_id', 'supplier_id', 'qty', 'harga_beli', 'harga_jual'])->all(),
            ...$this->dataPilihan(),
        ]);
    }

    public function update(TransaksiRequest $request, Transaksi $transaksi): RedirectResponse
    {
        $this->service->simpan($request->validated(), $transaksi);

        return redirect()->route('rekapan.show', $transaksi)
            ->with('success', "Nota {$transaksi->no_nota} berhasil diperbarui.");
    }

    public function destroy(Transaksi $transaksi): RedirectResponse
    {
        // Nota yang sudah punya pembayaran tidak boleh dihapus agar cashflow tetap konsisten
        if ($jumlah = $transaksi->pembayarans()->count()) {
            return back()->with('error', "Nota {$transaksi->no_nota} tidak dapat dihapus karena sudah memiliki {$jumlah} pembayaran. Hapus pembayarannya terlebih dahulu.");
        }

        DB::transaction(fn () => $transaksi->delete()); // detail ikut terhapus (cascade)

        return redirect()->route('rekapan.index')->with('success', "Nota {$transaksi->no_nota} berhasil dihapus.");
    }

    /**
     * Cetak nota untuk klien (hanya harga jual). ?pdf=1 untuk unduh PDF.
     */
    public function cetak(Request $request, Transaksi $transaksi): View|Response
    {
        $transaksi->load(['klien', 'details.barang:id,nama,satuan']);
        $dibayar = $transaksi->totalDibayar();
        $data = compact('transaksi', 'dibayar') + ['mode' => $request->boolean('pdf') ? 'pdf' : 'html'];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('rekapan.cetak', $data)
                ->setPaper('a5', 'portrait')
                ->download("Nota-{$transaksi->no_nota}.pdf");
        }

        return view('rekapan.cetak', $data);
    }

    /** Data dropdown klien/supplier/barang untuk form & filter. */
    private function dataPilihan(): array
    {
        return [
            'kliens' => Klien::orderBy('nama')->get(['id', 'nama']),
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
            'barangs' => Barang::orderBy('nama')->get(['id', 'nama', 'satuan', 'harga_beli_default', 'harga_jual_default']),
        ];
    }
}
