<?php

namespace App\Services;

use App\Models\Cashflow;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Support\Periode;
use Illuminate\Support\Facades\DB;

class ModalService
{
    /**
     * Modal tersedia = setoran modal - total beli nota + harga beli yang sudah terbayar klien.
     * Dihitung dari data aktif agar edit, hapus, dan pulihkan langsung tercermin.
     */
    public function ringkasan(Periode $periode): array
    {
        $harian = [];
        $tambah = function (string $tanggal, string $kolom, float $nilai) use (&$harian) {
            $harian[$tanggal] ??= ['tanggal' => $tanggal, 'tambahan' => 0.0, 'belanja' => 0.0, 'kembali' => 0.0];
            $harian[$tanggal][$kolom] += $nilai;
        };

        $setoran = Cashflow::whereNull('pembayaran_id')->where('kategori', 'Modal')
            ->where('tanggal', '<=', $periode->sampaiStr())
            ->groupBy('tanggal')
            ->get(['tanggal', DB::raw("SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE -nominal END) AS total")]);
        foreach ($setoran as $row) {
            $tambah($row->tanggal->toDateString(), 'tambahan', (float) $row->total);
        }

        $notas = Transaksi::where('tanggal', '<=', $periode->sampaiStr())
            ->get(['id', 'tanggal', 'total_beli', 'total_jual'])->keyBy('id');
        foreach ($notas as $nota) {
            $tambah($nota->tanggal->toDateString(), 'belanja', (float) $nota->total_beli);
        }

        // Pengembalian dihitung dari proporsi kumulatif, supaya cicilan kecil tidak meninggalkan selisih pembulatan.
        $dibayar = [];
        $pembayarans = Pembayaran::where('jenis', 'klien')->whereIn('transaksi_id', $notas->keys())
            ->where('tanggal', '<=', $periode->sampaiStr())->orderBy('tanggal')->orderBy('id')
            ->get(['transaksi_id', 'tanggal', 'jumlah']);
        foreach ($pembayarans as $pembayaran) {
            $nota = $notas[$pembayaran->transaksi_id];
            if ((float) $nota->total_jual <= 0) {
                continue;
            }
            $sebelum = $dibayar[$nota->id] ?? 0.0;
            $sesudah = $sebelum + (float) $pembayaran->jumlah;
            $bagianModal = fn (float $bayar) => round((float) $nota->total_beli * min(1, $bayar / (float) $nota->total_jual));
            $tambah($pembayaran->tanggal->toDateString(), 'kembali', $bagianModal($sesudah) - $bagianModal($sebelum));
            $dibayar[$nota->id] = $sesudah;
        }

        ksort($harian);
        $saldo = 0.0;
        $awal = 0.0;
        $rows = [];
        foreach ($harian as $tanggal => $row) {
            $saldo += $row['tambahan'] - $row['belanja'] + $row['kembali'];
            if ($tanggal < $periode->dariStr()) {
                $awal = $saldo;
            } else {
                $rows[] = (object) ($row + ['saldo' => $saldo]);
            }
        }

        $rows = collect($rows);

        return [
            'saldo_awal' => $awal,
            'tambahan' => (float) $rows->sum('tambahan'),
            'belanja' => (float) $rows->sum('belanja'),
            'kembali' => (float) $rows->sum('kembali'),
            'saldo_akhir' => $saldo,
            'harian' => $rows,
        ];
    }
}
