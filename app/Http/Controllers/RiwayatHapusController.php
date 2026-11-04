<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cashflow;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Services\TagihanSupplierService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Menu "Riwayat Hapus": data yang dihapus (soft delete) bisa dipulihkan
 * atau dihapus permanen.
 */
class RiwayatHapusController extends Controller
{
    /** Jenis data => [label, ikon, model]. */
    public const JENIS = [
        'nota' => ['Nota', 'bi-receipt', Transaksi::class],
        'pembayaran' => ['Pembayaran', 'bi-credit-card-2-front', Pembayaran::class],
        'cashflow' => ['Cashflow', 'bi-wallet2', Cashflow::class],
        'supplier' => ['Supplier', 'bi-truck', Supplier::class],
        'barang' => ['Barang', 'bi-basket2', Barang::class],
        'klien' => ['Klien', 'bi-building', Klien::class],
    ];

    public function index(Request $request): View
    {
        $jumlah = collect(self::JENIS)->map(fn ($j, $kunci) => $this->query($kunci)->count());

        // Default: tab pertama yang ada isinya
        $jenis = $request->input('jenis');
        if (! isset(self::JENIS[$jenis])) {
            $jenis = $jumlah->filter()->keys()->first() ?? 'nota';
        }

        $data = $this->query($jenis)->orderByDesc('deleted_at')->paginate(20)->withQueryString();

        return view('riwayat-hapus.index', [
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'data' => $data,
            'baris' => $data->getCollection()->map(fn ($m) => $this->ringkas($jenis, $m)),
        ]);
    }

    public function pulihkan(string $jenis, int $id): RedirectResponse
    {
        $model = $this->cari($jenis, $id);

        if ($alasan = $this->alasanTidakBisaDipulihkan($jenis, $model)) {
            return back()->with('error', $alasan);
        }

        DB::transaction(fn () => $model->restore());

        return back()->with('success', $this->ringkas($jenis, $model)['judul'].' berhasil dipulihkan.');
    }

    public function hapusPermanen(string $jenis, int $id): RedirectResponse
    {
        $model = $this->cari($jenis, $id);
        $judul = $this->ringkas($jenis, $model)['judul'];

        try {
            DB::transaction(fn () => $model->forceDelete());
        } catch (QueryException) {
            return back()->with('error', "{$judul} masih dipakai data lain di Riwayat Hapus (mis. nota/pembayaran). Hapus permanen data tersebut terlebih dahulu.");
        }

        return back()->with('success', "{$judul} dihapus permanen.");
    }

    /* ===================== Helper ===================== */

    private function query(string $jenis): Builder
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);
        $q = self::JENIS[$jenis][2]::onlyTrashed();

        return match ($jenis) {
            'nota' => $q->with('klien')->withCount('details'),
            'pembayaran' => $q->with(['klien', 'supplier', 'transaksi']),
            // Cashflow otomatis ikut pembayaran, yang tampil hanya cashflow manual
            'cashflow' => $q->whereNull('pembayaran_id'),
            'barang' => $q->with(['supplier' => fn ($s) => $s->withTrashed()]),
            default => $q,
        };
    }

    private function cari(string $jenis, int $id): Model
    {
        return $this->query($jenis)->findOrFail($id);
    }

    /** Ringkasan tampilan: judul + keterangan. */
    private function ringkas(string $jenis, Model $m): array
    {
        return match ($jenis) {
            'nota' => [
                'judul' => 'Nota '.$m->klien?->nama.' · '.tanggal($m->tanggal),
                'ket' => $m->details_count.' item · total jual '.rupiah($m->total_jual),
            ],
            'pembayaran' => [
                'judul' => ($m->jenis === 'klien' ? 'Terima dari ' : 'Bayar ke ').$m->nama_pihak.' · '.rupiah($m->jumlah),
                'ket' => tanggal($m->tanggal).' · '.(Pembayaran::METODE[$m->metode] ?? $m->metode)
                    .($m->tanggal_nota ? ' · Nota semua klien '.tanggal($m->tanggal_nota) : ($m->transaksi ? ' · Nota '.tanggal($m->transaksi->tanggal) : '')),
            ],
            'cashflow' => [
                'judul' => 'Cashflow '.($m->tipe === 'masuk' ? 'masuk' : 'keluar').' '.rupiah($m->nominal),
                'ket' => tanggal($m->tanggal).' · '.$m->kategori.($m->keterangan ? ' · '.$m->keterangan : ''),
            ],
            'supplier' => ['judul' => "Supplier \"{$m->nama}\"", 'ket' => $m->no_hp ?: '-', 'warna' => $m->warna],
            'barang' => [
                'judul' => "Barang \"{$m->nama}\"",
                'ket' => $m->satuan.' · jual '.rupiah($m->harga_jual_default).($m->supplier ? ' · '.$m->supplier->nama : ''),
            ],
            'klien' => ['judul' => "Klien \"{$m->nama}\"", 'ket' => $m->no_hp ?: '-'],
        };
    }

    /** Data induk yang ikut terhapus harus dipulihkan dulu agar data tetap konsisten. */
    private function alasanTidakBisaDipulihkan(string $jenis, Model $m): ?string
    {
        if ($jenis === 'nota' && $m->klien?->trashed()) {
            return "Klien \"{$m->klien->nama}\" juga ada di Riwayat Hapus. Pulihkan klien tersebut terlebih dahulu.";
        }

        if ($jenis === 'pembayaran') {
            if ($m->jenis === 'supplier' && $m->tanggal_nota) {
                $tagihan = app(TagihanSupplierService::class)->perTanggal($m->supplier_id)
                    ->firstWhere('tanggal', $m->tanggal_nota->toDateString());
                if (! $tagihan || (float) $m->jumlah > $tagihan->sisa + 0.009) {
                    return 'Pembayaran ini melebihi sisa total beli supplier pada tanggal nota, jadi tidak dapat dipulihkan.';
                }
            }
            $pihak = $m->jenis === 'klien' ? $m->klien : $m->supplier;
            if ($pihak?->trashed()) {
                return ($m->jenis === 'klien' ? 'Klien' : 'Supplier')." \"{$pihak->nama}\" juga ada di Riwayat Hapus. Pulihkan terlebih dahulu.";
            }
            if ($m->transaksi?->trashed()) {
                return 'Nota '.tanggal($m->transaksi->tanggal).' juga ada di Riwayat Hapus. Pulihkan nota tersebut terlebih dahulu.';
            }
            // Pembayaran klien tidak boleh membuat nota kelebihan bayar
            if ($m->jenis === 'klien' && $m->transaksi && (float) $m->jumlah > $m->transaksi->sisaTagihan() + 0.009) {
                return 'Pembayaran ini melebihi sisa tagihan nota saat ini ('.rupiah($m->transaksi->sisaTagihan()).'), jadi tidak dapat dipulihkan.';
            }
        }

        return null;
    }
}
