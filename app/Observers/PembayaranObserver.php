<?php

namespace App\Observers;

use App\Models\Cashflow;
use App\Models\Pembayaran;
use App\Models\Transaksi;

/**
 * Sinkronisasi otomatis Pembayaran -> Cashflow & status bayar nota.
 *  - klien    => cashflow "masuk"
 *  - supplier => cashflow "keluar"
 */
class PembayaranObserver
{
    public function created(Pembayaran $pembayaran): void
    {
        $pembayaran->cashflow()->create($this->dataCashflow($pembayaran));
        $this->refreshNota($pembayaran->transaksi_id);
    }

    public function updated(Pembayaran $pembayaran): void
    {
        $pembayaran->cashflow()->updateOrCreate(
            ['pembayaran_id' => $pembayaran->id],
            $this->dataCashflow($pembayaran)
        );

        // Nota lama & nota baru sama-sama dihitung ulang (bila nota dipindah)
        $this->refreshNota($pembayaran->getOriginal('transaksi_id'));
        $this->refreshNota($pembayaran->transaksi_id);
    }

    /** Dihapus (ke Riwayat Hapus): cashflow otomatisnya dibuang, status nota dihitung ulang. */
    public function deleted(Pembayaran $pembayaran): void
    {
        $pembayaran->cashflow()->withTrashed()->forceDelete();
        $this->refreshNota($pembayaran->transaksi_id);
    }

    /** Dipulihkan dari Riwayat Hapus: cashflow dibuat lagi. */
    public function restored(Pembayaran $pembayaran): void
    {
        $pembayaran->cashflow()->withTrashed()->forceDelete();
        $pembayaran->cashflow()->create($this->dataCashflow($pembayaran));
        $this->refreshNota($pembayaran->transaksi_id);
    }

    private function dataCashflow(Pembayaran $p): array
    {
        $p->loadMissing(['klien', 'supplier', 'transaksi']);
        $nota = $p->transaksi ? ' · Nota '.tanggal($p->transaksi->tanggal) : '';

        return [
            'tanggal' => $p->tanggal,
            'tipe' => $p->jenis === 'klien' ? 'masuk' : 'keluar',
            'kategori' => $p->jenis === 'klien' ? Cashflow::KATEGORI_KLIEN : Cashflow::KATEGORI_SUPPLIER,
            'nominal' => $p->jumlah,
            'keterangan' => ($p->jenis === 'klien' ? 'Terima dari ' : 'Bayar ke ').$p->nama_pihak.$nota
                .' ('.ucfirst($p->metode).')',
        ];
    }

    private function refreshNota(?int $transaksiId): void
    {
        if ($transaksiId) {
            Transaksi::find($transaksiId)?->refreshStatusBayar();
        }
    }
}
