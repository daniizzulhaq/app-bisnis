<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembayaranRequest;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pembayaran klien (uang masuk / pelunasan piutang) dan
 * pembayaran supplier (uang keluar / pelunasan hutang).
 * Cashflow & status nota disinkronkan oleh PembayaranObserver.
 */
class PembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->only(['dari', 'sampai', 'jenis', 'klien_id', 'supplier_id', 'metode']);

        $query = Pembayaran::query()
            ->when($filter['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($filter['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($filter['jenis'] ?? null, fn ($q, $v) => $q->where('jenis', $v))
            ->when($filter['klien_id'] ?? null, fn ($q, $v) => $q->where('klien_id', $v))
            ->when($filter['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filter['metode'] ?? null, fn ($q, $v) => $q->where('metode', $v));

        $ringkasan = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis='klien' THEN jumlah END),0) AS masuk,
                COALESCE(SUM(CASE WHEN jenis='supplier' THEN jumlah END),0) AS keluar")
            ->first();

        $pembayarans = $query
            ->with(['klien:id,nama', 'supplier:id,nama', 'transaksi:id,tanggal'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('pembayaran.index', [
            'pembayarans' => $pembayarans,
            'ringkasan' => $ringkasan,
            'filter' => $filter,
            'kliens' => Klien::orderBy('nama')->get(['id', 'nama']),
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(Request $request): View
    {
        $pembayaran = new Pembayaran(['tanggal' => now(), 'jenis' => 'klien', 'metode' => 'tunai']);

        // Prefill dari halaman detail nota: ?transaksi=ID
        if ($nota = Transaksi::find($request->integer('transaksi'))) {
            $pembayaran->fill([
                'jenis' => 'klien',
                'klien_id' => $nota->klien_id,
                'transaksi_id' => $nota->id,
                'jumlah' => $nota->sisaTagihan(),
            ]);
        }

        return view('pembayaran.form', $this->dataForm($pembayaran));
    }

    public function store(PembayaranRequest $request): RedirectResponse
    {
        DB::transaction(fn () => Pembayaran::create($request->validated()));

        return $this->kembali($request)->with('success', 'Pembayaran berhasil dicatat & cashflow diperbarui.');
    }

    public function edit(Pembayaran $pembayaran): View
    {
        return view('pembayaran.form', $this->dataForm($pembayaran));
    }

    public function update(PembayaranRequest $request, Pembayaran $pembayaran): RedirectResponse
    {
        DB::transaction(fn () => $pembayaran->update($request->validated()));

        return $this->kembali($request)->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function destroy(Pembayaran $pembayaran): RedirectResponse
    {
        DB::transaction(fn () => $pembayaran->delete());

        return back()->with('success', 'Pembayaran dihapus, cashflow & status nota telah disesuaikan. Bisa dipulihkan dari menu Riwayat Hapus.');
    }

    /**
     * JSON pilihan nota untuk dropdown (bergantung pada jenis & pihak).
     * GET pembayaran/nota?jenis=klien&id=3
     */
    public function notaOptions(Request $request): JsonResponse
    {
        $id = $request->integer('id');
        $pilih = $request->integer('selected');

        if ($request->jenis === 'supplier') {
            // Total belanja ke supplier ini per nota
            $rows = TransaksiDetail::query()
                ->join('transaksis', 'transaksis.id', '=', 'transaksi_details.transaksi_id')
                ->where('transaksi_details.supplier_id', $id)
                ->whereNull('transaksis.deleted_at')
                ->join('kliens', 'kliens.id', '=', 'transaksis.klien_id')
                ->groupBy('transaksis.id', 'transaksis.tanggal', 'kliens.nama')
                ->orderByDesc('transaksis.tanggal')
                ->limit(100)
                ->get(['transaksis.id', 'transaksis.tanggal', 'kliens.nama AS klien', DB::raw('SUM(subtotal_beli) AS total')]);

            return response()->json($rows->map(fn ($r) => [
                'value' => $r->id,
                'text' => tanggal($r->tanggal)." · {$r->klien} · beli ".rupiah($r->total),
            ]));
        }

        // Nota klien yang belum lunas (+ nota yang sedang dipilih saat edit)
        $rows = Transaksi::query()
            ->where('klien_id', $id)
            ->where(fn ($q) => $q->where('status_bayar', '!=', 'lunas')->orWhere('id', $pilih))
            ->withSum('pembayaranKlien as dibayar', 'jumlah')
            ->orderByDesc('tanggal')
            ->limit(100)
            ->get(['id', 'tanggal', 'total_jual']);

        return response()->json($rows->map(fn ($t) => [
            'value' => $t->id,
            'text' => 'Nota '.tanggal($t->tanggal).' · total '.rupiah($t->total_jual).' · sisa '.rupiah(max(0, $t->total_jual - $t->dibayar)),
            'sisa' => max(0, (float) $t->total_jual - (float) $t->dibayar),
        ]));
    }

    private function dataForm(Pembayaran $pembayaran): array
    {
        return [
            'pembayaran' => $pembayaran,
            'kliens' => Klien::orderBy('nama')->get(['id', 'nama']),
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
        ];
    }

    /** Setelah simpan: kembali ke nota bila pembayaran terkait nota dari halaman detail. */
    private function kembali(Request $request): RedirectResponse
    {
        if ($request->filled('kembali_ke_nota') && $request->transaksi_id) {
            return redirect()->route('rekapan.show', $request->transaksi_id);
        }

        return redirect()->route('pembayaran.index');
    }
}
