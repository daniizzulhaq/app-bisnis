<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashflowRequest;
use App\Models\Cashflow;
use App\Support\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashflowController extends Controller
{
    private const PER_HALAMAN = 20;

    public function index(Request $request): View
    {
        $periode = Periode::dariRequest($request);
        $tipe = $request->input('tipe');
        $kategori = $request->input('kategori');

        $saldoAwal = Cashflow::saldoSebelum($periode->dariStr());

        // Total masuk/keluar periode (tanpa filter tipe/kategori agar saldo akhir benar)
        $total = Cashflow::whereBetween('tanggal', [$periode->dariStr(), $periode->sampaiStr()])
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe='masuk' THEN nominal END),0) AS masuk,
                COALESCE(SUM(CASE WHEN tipe='keluar' THEN nominal END),0) AS keluar")
            ->first();
        $saldoAkhir = $saldoAwal + $total->masuk - $total->keluar;

        $base = Cashflow::whereBetween('tanggal', [$periode->dariStr(), $periode->sampaiStr()])
            ->when($tipe, fn ($q, $v) => $q->where('tipe', $v))
            ->when($kategori, fn ($q, $v) => $q->where('kategori', $v));

        $cashflows = (clone $base)
            ->with('pembayaran:id,transaksi_id')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        // Saldo berjalan: hanya bermakna bila tidak memfilter tipe/kategori
        $saldoBerjalan = null;
        if (! $tipe && ! $kategori) {
            $offset = ($cashflows->currentPage() - 1) * self::PER_HALAMAN;
            $saldoBerjalan = $saldoAwal + ($offset > 0 ? (float) DB::query()
                ->fromSub((clone $base)->select('tipe', 'nominal')->orderBy('tanggal')->orderBy('id')->limit($offset), 'x')
                ->selectRaw("COALESCE(SUM(CASE WHEN tipe='masuk' THEN nominal ELSE -nominal END),0) AS s")
                ->value('s') : 0);
        }

        return view('cashflow.index', [
            'periode' => $periode,
            'cashflows' => $cashflows,
            'saldoAwal' => $saldoAwal,
            'totalMasuk' => (float) $total->masuk,
            'totalKeluar' => (float) $total->keluar,
            'saldoAkhir' => $saldoAkhir,
            'saldoBerjalan' => $saldoBerjalan,
            'kategoris' => Cashflow::distinct()->orderBy('kategori')->pluck('kategori'),
        ]);
    }

    public function create(): View
    {
        return view('cashflow.form', ['cashflow' => new Cashflow(['tanggal' => now(), 'tipe' => 'keluar'])]);
    }

    public function store(CashflowRequest $request): RedirectResponse
    {
        $cashflow = Cashflow::create($request->validated());

        // Tampilkan bulan dari tanggal yang baru diinput
        return redirect()->route('cashflow.index', [
            'dari' => $cashflow->tanggal->copy()->startOfMonth()->toDateString(),
            'sampai' => $cashflow->tanggal->copy()->endOfMonth()->toDateString(),
        ])
            ->with('success', 'Cashflow manual berhasil dicatat.');
    }

    public function edit(Cashflow $cashflow): View|RedirectResponse
    {
        if ($cashflow->isOtomatis()) {
            return $this->tolakOtomatis();
        }

        return view('cashflow.form', compact('cashflow'));
    }

    public function update(CashflowRequest $request, Cashflow $cashflow): RedirectResponse
    {
        if ($cashflow->isOtomatis()) {
            return $this->tolakOtomatis();
        }

        $cashflow->update($request->validated());

        return redirect()->route('cashflow.index')->with('success', 'Cashflow berhasil diperbarui.');
    }

    public function destroy(Cashflow $cashflow): RedirectResponse
    {
        if ($cashflow->isOtomatis()) {
            return $this->tolakOtomatis();
        }

        $cashflow->delete();

        return back()->with('success', 'Cashflow berhasil dihapus.');
    }

    private function tolakOtomatis(): RedirectResponse
    {
        return redirect()->route('cashflow.index')
            ->with('error', 'Baris ini dibuat otomatis dari Pembayaran. Ubah atau hapus lewat menu Pembayaran.');
    }
}
