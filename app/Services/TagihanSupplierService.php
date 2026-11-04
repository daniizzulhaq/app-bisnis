<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\TransaksiDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TagihanSupplierService
{
    /** Total beli supplier dari seluruh klien, dikelompokkan menurut tanggal nota. */
    public function perTanggal(int $supplierId, ?int $abaikanPembayaran = null): Collection
    {
        $dibayar = Pembayaran::query()
            ->leftJoin('transaksis as t', 't.id', '=', 'pembayarans.transaksi_id')
            ->where('pembayarans.jenis', 'supplier')
            ->where('pembayarans.supplier_id', $supplierId)
            ->when($abaikanPembayaran, fn ($q) => $q->where('pembayarans.id', '!=', $abaikanPembayaran))
            ->groupByRaw('COALESCE(pembayarans.tanggal_nota, t.tanggal)')
            ->selectRaw('COALESCE(pembayarans.tanggal_nota, t.tanggal) AS tanggal_nota, SUM(pembayarans.jumlah) AS total')
            ->pluck('total', 'tanggal_nota');

        return TransaksiDetail::query()
            ->join('transaksis as t', 't.id', '=', 'transaksi_details.transaksi_id')
            ->whereNull('t.deleted_at')
            ->where('transaksi_details.supplier_id', $supplierId)
            ->groupBy('t.tanggal')
            ->orderByDesc('t.tanggal')
            ->get(['t.tanggal', DB::raw('SUM(subtotal_beli) AS total'), DB::raw('COUNT(DISTINCT t.id) AS nota')])
            ->map(function ($row) use ($dibayar) {
                $row->total = (float) $row->total;
                $row->dibayar = (float) ($dibayar[$row->tanggal] ?? 0);
                $row->sisa = max(0, $row->total - $row->dibayar);

                return $row;
            });
    }
}
