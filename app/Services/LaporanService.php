<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Support\Periode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query laporan dipusatkan di sini supaya halaman web, PDF, dan Excel
 * memakai angka yang persis sama.
 *
 * Definisi:
 *  - Total jual/beli & dibayar  : transaksi/pembayaran DI DALAM periode.
 *  - Sisa piutang/hutang        : saldo KUMULATIF s/d tanggal akhir periode
 *                                 (semua jual/beli s/d tgl akhir - semua bayar s/d tgl akhir).
 */
class LaporanService
{
    /* ===================== PER KLIEN ===================== */

    public function perKlien(Periode $p, ?int $klienId = null): array
    {
        [$dari, $sampai] = [$p->dariStr(), $p->sampaiStr()];

        $jual = Transaksi::whereBetween('tanggal', [$dari, $sampai])
            ->when($klienId, fn ($q) => $q->where('klien_id', $klienId))
            ->groupBy('klien_id')
            ->get(['klien_id', DB::raw('COUNT(*) AS nota'), DB::raw('SUM(total_jual) AS total')])
            ->keyBy('klien_id');

        $bayar = $this->sumPembayaran('klien', 'klien_id', $dari, $sampai, $klienId);
        $jualKum = Transaksi::where('tanggal', '<=', $sampai)
            ->when($klienId, fn ($q) => $q->where('klien_id', $klienId))
            ->groupBy('klien_id')->selectRaw('klien_id, SUM(total_jual) AS total')->pluck('total', 'klien_id');
        $bayarKum = $this->sumPembayaran('klien', 'klien_id', null, $sampai, $klienId);

        $ids = $jual->keys()->merge($bayar->keys())->merge($jualKum->keys())->unique();
        $nama = Klien::whereIn('id', $ids)->pluck('nama', 'id');

        $rows = $ids->map(fn ($id) => (object) [
            'id' => $id,
            'nama' => $nama[$id] ?? '-',
            'nota' => (int) ($jual[$id]->nota ?? 0),
            'total_jual' => (float) ($jual[$id]->total ?? 0),
            'dibayar' => (float) ($bayar[$id] ?? 0),
            'sisa' => (float) ($jualKum[$id] ?? 0) - (float) ($bayarKum[$id] ?? 0),
        ])
            // Sembunyikan klien tanpa aktivitas & tanpa sisa piutang
            ->filter(fn ($r) => $r->nota || $r->dibayar || abs($r->sisa) > 0.009)
            ->sortBy('nama')->values();

        // Rincian nota hanya bila 1 klien dipilih
        $notas = $klienId
            ? Transaksi::where('klien_id', $klienId)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->withSum('pembayaranKlien as dibayar', 'jumlah')
                ->orderBy('tanggal')->orderBy('id')
                ->get()
            : collect();

        return [
            'rows' => $rows,
            'notas' => $notas,
            'klien' => $klienId ? Klien::find($klienId) : null,
            'total' => $this->jumlahkan($rows, ['nota', 'total_jual', 'dibayar', 'sisa']),
        ];
    }

    /* ===================== PER SUPPLIER ===================== */

    public function perSupplier(Periode $p, ?int $supplierId = null): array
    {
        [$dari, $sampai] = [$p->dariStr(), $p->sampaiStr()];

        $beli = $this->detailQuery($dari, $sampai)
            ->when($supplierId, fn ($q) => $q->where('d.supplier_id', $supplierId))
            ->groupBy('d.supplier_id')
            ->get([
                'd.supplier_id',
                DB::raw('COUNT(DISTINCT d.transaksi_id) AS nota'),
                DB::raw('SUM(d.qty) AS qty'),
                DB::raw('SUM(d.subtotal_beli) AS total'),
            ])->keyBy('supplier_id');

        $bayar = $this->sumPembayaran('supplier', 'supplier_id', $dari, $sampai, $supplierId);
        $beliKum = $this->detailQuery(null, $sampai)
            ->when($supplierId, fn ($q) => $q->where('d.supplier_id', $supplierId))
            ->groupBy('d.supplier_id')->selectRaw('d.supplier_id, SUM(d.subtotal_beli) AS total')->pluck('total', 'supplier_id');
        $bayarKum = $this->sumPembayaran('supplier', 'supplier_id', null, $sampai, $supplierId);

        $ids = $beli->keys()->merge($bayar->keys())->merge($beliKum->keys())->unique();
        $nama = Supplier::whereIn('id', $ids)->pluck('nama', 'id');

        $rows = $ids->map(fn ($id) => (object) [
            'id' => $id,
            'nama' => $nama[$id] ?? '-',
            'nota' => (int) ($beli[$id]->nota ?? 0),
            'qty' => (float) ($beli[$id]->qty ?? 0),
            'total_beli' => (float) ($beli[$id]->total ?? 0),
            'dibayar' => (float) ($bayar[$id] ?? 0),
            'sisa' => (float) ($beliKum[$id] ?? 0) - (float) ($bayarKum[$id] ?? 0),
        ])
            ->filter(fn ($r) => $r->nota || $r->dibayar || abs($r->sisa) > 0.009)
            ->sortBy('nama')->values();

        // Rincian item yang dibeli bila 1 supplier dipilih
        $items = $supplierId
            ? TransaksiDetail::where('supplier_id', $supplierId)
                ->whereHas('transaksi', fn ($q) => $q->whereBetween('tanggal', [$dari, $sampai]))
                ->with(['transaksi:id,no_nota,tanggal,klien_id', 'transaksi.klien:id,nama', 'barang:id,nama,satuan'])
                ->get()
                ->sortBy(fn ($d) => $d->transaksi->tanggal->format('Ymd').str_pad((string) $d->id, 10, '0', STR_PAD_LEFT))
                ->values()
            : collect();

        return [
            'rows' => $rows,
            'items' => $items,
            'supplier' => $supplierId ? Supplier::find($supplierId) : null,
            'total' => $this->jumlahkan($rows, ['nota', 'qty', 'total_beli', 'dibayar', 'sisa']),
        ];
    }

    /* ===================== PER BARANG ===================== */

    public function perBarang(Periode $p, ?int $barangId = null): array
    {
        $data = $this->detailQuery($p->dariStr(), $p->sampaiStr())
            ->when($barangId, fn ($q) => $q->where('d.barang_id', $barangId))
            ->groupBy('d.barang_id')
            ->get([
                'd.barang_id',
                DB::raw('COUNT(DISTINCT d.transaksi_id) AS nota'),
                DB::raw('SUM(d.qty) AS qty'),
                DB::raw('SUM(d.subtotal_beli) AS beli'),
                DB::raw('SUM(d.subtotal_jual) AS jual'),
            ]);

        $barang = Barang::whereIn('id', $data->pluck('barang_id'))->get(['id', 'nama', 'satuan'])->keyBy('id');

        $rows = $data->map(fn ($r) => (object) [
            'id' => $r->barang_id,
            'nama' => $barang[$r->barang_id]->nama ?? '-',
            'satuan' => $barang[$r->barang_id]->satuan ?? '',
            'nota' => (int) $r->nota,
            'qty' => (float) $r->qty,
            'total_beli' => (float) $r->beli,
            'total_jual' => (float) $r->jual,
            'laba' => (float) $r->jual - (float) $r->beli,
            'margin' => (float) $r->jual > 0 ? ((float) $r->jual - (float) $r->beli) / (float) $r->jual * 100 : 0,
        ])->sortByDesc('total_jual')->values();

        $total = $this->jumlahkan($rows, ['total_beli', 'total_jual', 'laba']);
        $total->margin = $total->total_jual > 0 ? $total->laba / $total->total_jual * 100 : 0;

        return ['rows' => $rows, 'total' => $total];
    }

    /* ===================== Helper ===================== */

    /** Query detail transaksi yang sudah di-join dengan header (untuk filter tanggal). */
    private function detailQuery(?string $dari, string $sampai)
    {
        return DB::table('transaksi_details as d')
            ->join('transaksis as t', 't.id', '=', 'd.transaksi_id')
            ->when($dari, fn ($q) => $q->where('t.tanggal', '>=', $dari))
            ->where('t.tanggal', '<=', $sampai);
    }

    /** Total pembayaran per pihak: [pihak_id => jumlah]. */
    private function sumPembayaran(string $jenis, string $kolom, ?string $dari, string $sampai, ?int $id): Collection
    {
        return Pembayaran::where('jenis', $jenis)
            ->when($dari, fn ($q) => $q->where('tanggal', '>=', $dari))
            ->where('tanggal', '<=', $sampai)
            ->when($id, fn ($q) => $q->where($kolom, $id))
            ->groupBy($kolom)
            ->selectRaw("{$kolom}, SUM(jumlah) AS total")
            ->pluck('total', $kolom);
    }

    private function jumlahkan(Collection $rows, array $kolom): object
    {
        $hasil = new \stdClass;
        foreach ($kolom as $k) {
            $hasil->{$k} = (float) $rows->sum($k);
        }

        return $hasil;
    }
}
