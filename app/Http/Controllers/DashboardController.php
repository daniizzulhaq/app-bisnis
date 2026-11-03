<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periode = Periode::dariRequest($request);
        [$dari, $sampai] = [$periode->dariStr(), $periode->sampaiStr()];

        $stat = Transaksi::whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('COUNT(*) AS nota, COALESCE(SUM(total_qty),0) AS qty, COALESCE(SUM(total_jual),0) AS jual,
                COALESCE(SUM(total_beli),0) AS beli, COALESCE(SUM(laba),0) AS laba')
            ->first();

        // Detail transaksi dalam periode, dipakai untuk Top Supplier & Top Barang
        $detail = fn () => DB::table('transaksi_details as d')
            ->join('transaksis as t', 't.id', '=', 'd.transaksi_id')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.tanggal', [$dari, $sampai]);

        $topSupplier = $detail()
            ->join('suppliers as s', 's.id', '=', 'd.supplier_id')
            ->groupBy('s.id', 's.nama')
            ->orderByDesc('total')
            ->limit(3)
            ->get(['s.nama', DB::raw('SUM(d.subtotal_beli) AS total'), DB::raw('COUNT(DISTINCT d.transaksi_id) AS nota')]);

        $topBarang = $detail()
            ->join('barangs as b', 'b.id', '=', 'd.barang_id')
            ->groupBy('b.id', 'b.nama', 'b.satuan')
            ->orderByDesc('qty')
            ->limit(3)
            ->get(['b.nama', 'b.satuan', DB::raw('SUM(d.qty) AS qty'), DB::raw('SUM(d.subtotal_jual) AS total')]);

        $topKlien = DB::table('transaksis as t')
            ->join('kliens as k', 'k.id', '=', 't.klien_id')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.tanggal', [$dari, $sampai])
            ->groupBy('k.id', 'k.nama')
            ->orderByDesc('total')
            ->limit(3)
            ->get(['k.nama', DB::raw('SUM(t.total_jual) AS total'), DB::raw('COUNT(*) AS nota')]);

        $terakhir = Transaksi::with('klien:id,nama')
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', compact('periode', 'stat', 'topSupplier', 'topBarang', 'topKlien', 'terakhir'));
    }
}
