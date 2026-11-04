<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Support\OpsiLaporan;
use App\Support\Periode;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query laporan dipusatkan di sini supaya halaman web, PDF, dan Excel
 * memakai angka yang persis sama.
 *
 * Definisi:
 *  - Qty, total beli/jual, laba : item transaksi DI DALAM periode, mengikuti filter klien/supplier/barang.
 *  - Dibayar                    : pembayaran DI DALAM periode (per klien / per supplier).
 *  - Sisa piutang/hutang        : saldo KUMULATIF s/d tanggal akhir periode.
 *    Dibayar & sisa dihitung per pihak secara keseluruhan (tidak dipecah per barang/supplier),
 *    karena pembayaran dicatat per klien/supplier, bukan per item.
 */
class LaporanService
{
    /* ===================== PER KLIEN ===================== */

    public function perKlien(Periode $p, OpsiLaporan $o): array
    {
        [$dari, $sampai] = [$p->dariStr(), $p->sampaiStr()];

        $agg = $this->agregat($this->detailQuery($dari, $sampai, $o), 't.klien_id');

        $bayar = $this->sumPembayaran('klien', 'klien_id', $dari, $sampai, $o->klienIds);
        $jualKum = Transaksi::where('tanggal', '<=', $sampai)
            ->when($o->klienIds, fn ($q) => $q->whereIn('klien_id', $o->klienIds))
            ->groupBy('klien_id')->selectRaw('klien_id, SUM(total_jual) AS total')->pluck('total', 'klien_id');
        $bayarKum = $this->sumPembayaran('klien', 'klien_id', null, $sampai, $o->klienIds);

        // Saat difilter supplier/barang, hanya klien yang punya item cocok yang tampil
        $ids = $agg->keys();
        if (! $o->supplierIds && ! $o->barangIds) {
            $ids = $ids->merge($bayar->keys())->merge($jualKum->keys());
        }
        $ids = $ids->unique();
        $nama = Klien::whereIn('id', $ids)->pluck('nama', 'id');

        $rows = $ids->map(fn ($id) => (object) [
            'id' => $id,
            'nama' => $nama[$id] ?? '-',
            ...$this->angkaAgregat($agg[$id] ?? null),
            'dibayar' => (float) ($bayar[$id] ?? 0),
            'sisa' => (float) ($jualKum[$id] ?? 0) - (float) ($bayarKum[$id] ?? 0),
        ])
            // Sembunyikan klien tanpa aktivitas & tanpa sisa piutang
            ->filter(fn ($r) => $r->nota || $r->dibayar || abs($r->sisa) > 0.009)
            ->sortBy('nama')->values();

        return [
            'rows' => $rows,
            'rincian' => $this->rincian($dari, $sampai, $o, 'klien_id', $nama),
            'total' => $this->jumlahkan($rows, ['nota', 'qty', 'total_beli', 'total_jual', 'laba', 'dibayar', 'sisa']),
        ];
    }

    /* ===================== PER SUPPLIER ===================== */

    public function perSupplier(Periode $p, OpsiLaporan $o): array
    {
        [$dari, $sampai] = [$p->dariStr(), $p->sampaiStr()];

        $agg = $this->agregat($this->detailQuery($dari, $sampai, $o), 'd.supplier_id');

        $bayar = $this->sumPembayaran('supplier', 'supplier_id', $dari, $sampai, $o->supplierIds);
        $beliKum = $this->detailQuery(null, $sampai, new OpsiLaporan(supplierIds: $o->supplierIds))
            ->groupBy('d.supplier_id')->selectRaw('d.supplier_id, SUM(d.subtotal_beli) AS total')->pluck('total', 'supplier_id');
        $bayarKum = $this->sumPembayaran('supplier', 'supplier_id', null, $sampai, $o->supplierIds);

        $ids = $agg->keys();
        if (! $o->klienIds && ! $o->barangIds) {
            $ids = $ids->merge($bayar->keys())->merge($beliKum->keys());
        }
        $ids = $ids->unique();
        $supplier = Supplier::whereIn('id', $ids)->get(['id', 'nama', 'warna'])->keyBy('id');

        $rows = $ids->map(fn ($id) => (object) [
            'id' => $id,
            'nama' => $supplier[$id]->nama ?? '-',
            'warna' => $supplier[$id]->warna ?? null,
            ...$this->angkaAgregat($agg[$id] ?? null),
            'dibayar' => (float) ($bayar[$id] ?? 0),
            'sisa' => (float) ($beliKum[$id] ?? 0) - (float) ($bayarKum[$id] ?? 0),
        ])
            ->filter(fn ($r) => $r->nota || $r->dibayar || abs($r->sisa) > 0.009)
            ->sortBy('nama')->values();

        return [
            'rows' => $rows,
            'rincian' => $o->supplierIds ? $this->rincian($dari, $sampai, $o, 'supplier_id', $supplier->pluck('nama', 'id'), $o->tampilKlien) : collect(),
            'total' => $this->jumlahkan($rows, ['nota', 'qty', 'total_beli', 'total_jual', 'laba', 'dibayar', 'sisa']),
        ];
    }

    /* ===================== PER BARANG ===================== */

    public function perBarang(Periode $p, OpsiLaporan $o): array
    {
        $agg = $this->agregat($this->detailQuery($p->dariStr(), $p->sampaiStr(), $o), 'd.barang_id');

        $barang = Barang::with('supplier:id,nama,warna')->whereIn('id', $agg->keys())
            ->get(['id', 'supplier_id', 'nama', 'satuan'])->keyBy('id');

        $rows = $agg->map(fn ($r, $id) => (object) [
            'id' => $id,
            'nama' => $barang[$id]->nama ?? '-',
            'satuan' => $barang[$id]->satuan ?? '',
            'supplier' => $barang[$id]->supplier ?? null,
            ...$this->angkaAgregat($r),
        ])->sortByDesc(fn ($r) => $r->total_jual ?: $r->total_beli)->values();

        $total = $this->jumlahkan($rows, ['nota', 'total_beli', 'total_jual', 'laba']);
        $total->margin = $total->total_jual > 0 ? $total->laba / $total->total_jual * 100 : 0;

        return ['rows' => $rows, 'rincian' => collect(), 'total' => $total];
    }

    /* ===================== Helper ===================== */

    /**
     * Query item transaksi yang sudah di-join dengan header, difilter periode
     * dan pilihan klien/supplier/barang.
     */
    private function detailQuery(?string $dari, string $sampai, OpsiLaporan $o): Builder
    {
        return DB::table('transaksi_details as d')
            ->join('transaksis as t', 't.id', '=', 'd.transaksi_id')
            ->whereNull('t.deleted_at')
            ->when($dari, fn ($q) => $q->where('t.tanggal', '>=', $dari))
            ->where('t.tanggal', '<=', $sampai)
            ->when($o->klienIds, fn ($q) => $q->whereIn('t.klien_id', $o->klienIds))
            ->when($o->supplierIds, fn ($q) => $q->whereIn('d.supplier_id', $o->supplierIds))
            ->when($o->barangIds, fn ($q) => $q->whereIn('d.barang_id', $o->barangIds));
    }

    /** Jumlah nota, qty, beli, jual per kolom kelompok: [id => row]. */
    private function agregat(Builder $query, string $kolom): Collection
    {
        $alias = substr($kolom, strpos($kolom, '.') + 1);

        return $query->groupBy($kolom)
            ->get([
                $kolom,
                DB::raw('COUNT(DISTINCT d.transaksi_id) AS nota'),
                DB::raw('SUM(d.qty) AS qty'),
                DB::raw('SUM(d.subtotal_beli) AS beli'),
                DB::raw('SUM(d.subtotal_jual) AS jual'),
            ])->keyBy($alias);
    }

    private function angkaAgregat(?object $r): array
    {
        $beli = (float) ($r->beli ?? 0);
        $jual = (float) ($r->jual ?? 0);

        return [
            'nota' => (int) ($r->nota ?? 0),
            'qty' => (float) ($r->qty ?? 0),
            'total_beli' => $beli,
            'total_jual' => $jual,
            'laba' => $jual - $beli,
            'margin' => $jual > 0 ? ($jual - $beli) / $jual * 100 : 0,
        ];
    }

    /**
     * Rincian item per pihak terpilih (klien_id / supplier_id), mengikuti semua filter.
     * Hasil: [ ['id', 'nama', 'items' => Collection<TransaksiDetail>], ... ]
     */
    private function rincian(string $dari, string $sampai, OpsiLaporan $o, string $kunci, Collection $nama, bool $pisahKlien = true): Collection
    {
        $items = TransaksiDetail::query()
            ->select('transaksi_details.*', 'transaksis.tanggal', 'transaksis.klien_id')
            ->join('transaksis', 'transaksis.id', '=', 'transaksi_details.transaksi_id')
            ->whereNull('transaksis.deleted_at')
            ->whereBetween('transaksis.tanggal', [$dari, $sampai])
            ->when($o->klienIds, fn ($q) => $q->whereIn('transaksis.klien_id', $o->klienIds))
            ->when($o->supplierIds, fn ($q) => $q->whereIn('transaksi_details.supplier_id', $o->supplierIds))
            ->when($o->barangIds, fn ($q) => $q->whereIn('transaksi_details.barang_id', $o->barangIds))
            ->with(['barang:id,nama,satuan', 'supplier:id,nama,warna', 'transaksi:id,klien_id', 'transaksi.klien:id,nama'])
            ->orderBy('transaksis.tanggal')->orderBy('transaksi_details.transaksi_id')->orderBy('transaksi_details.id')
            ->get();

        $items = $this->gabungPerTanggal($items, $pisahKlien);
        $ids = $kunci === 'klien_id' ? $o->klienIds : $o->supplierIds;
        $ids = $ids ?: $items->pluck($kunci)->unique()->all();

        return collect($ids)
            ->map(fn ($id) => (object) [
                'id' => $id,
                'nama' => $nama[$id] ?? ($kunci === 'klien_id' ? Klien::find($id)?->nama : Supplier::find($id)?->nama) ?? '-',
                'items' => $items->where($kunci, $id)->values(),
            ])
            ->sortBy('nama')->values();
    }

    /**
     * Barang yang sama (supplier sama; klien sama bila $pisahKlien) di tanggal yang sama digabung jadi satu baris,
     * mis. pesanan awal + tambahan, walaupun harganya berbeda. Qty tiap pesanan disimpan di atribut
     * "pecahan" (untuk format 1 + 1), harga yang berbeda di "harga_beli_list" / "harga_jual_list".
     */
    private function gabungPerTanggal(Collection $items, bool $pisahKlien = true): Collection
    {
        return $items
            ->groupBy(fn ($d) => implode('|', [$d->tanggal, $pisahKlien ? $d->klien_id : '*', $d->barang_id, $d->supplier_id]))
            ->map(function (Collection $grup) {
                $baris = clone $grup->first();
                $baris->qty = $grup->sum(fn ($d) => (float) $d->qty);
                $baris->subtotal_beli = $grup->sum(fn ($d) => (float) $d->subtotal_beli);
                $baris->subtotal_jual = $grup->sum(fn ($d) => (float) $d->subtotal_jual);
                $baris->pecahan = $grup->map(fn ($d) => (float) $d->qty)->all();
                $baris->harga_beli_list = $grup->map(fn ($d) => (float) $d->harga_beli)->unique()->values()->all();
                $baris->harga_jual_list = $grup->map(fn ($d) => (float) $d->harga_jual)->unique()->values()->all();

                return $baris;
            })
            ->values();
    }

    /** Total pembayaran per pihak: [pihak_id => jumlah]. */
    private function sumPembayaran(string $jenis, string $kolom, ?string $dari, string $sampai, array $ids): Collection
    {
        return Pembayaran::where('jenis', $jenis)
            ->when($dari, fn ($q) => $q->where('tanggal', '>=', $dari))
            ->where('tanggal', '<=', $sampai)
            ->when($ids, fn ($q) => $q->whereIn($kolom, $ids))
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
