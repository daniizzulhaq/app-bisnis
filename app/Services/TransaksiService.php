<?php

namespace App\Services;

use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class TransaksiService
{
    /**
     * Simpan nota baru atau perbarui nota lama (header + detail) secara atomik.
     *
     * Subtotal & total SELALU dihitung ulang di server; angka dari JavaScript
     * hanya untuk tampilan dan tidak pernah dipercaya.
     */
    public function simpan(array $data, ?Transaksi $transaksi = null): Transaksi
    {
        return DB::transaction(function () use ($data, $transaksi) {
            $items = $this->hitungItems($data['items']);

            $header = [
                'tanggal' => $data['tanggal'],
                'klien_id' => $data['klien_id'],
                'keterangan' => $data['keterangan'] ?? null,
                'total_qty' => round(array_sum(array_column($items, 'qty')), 2),
                'total_beli' => array_sum(array_column($items, 'subtotal_beli')),
                'total_jual' => array_sum(array_column($items, 'subtotal_jual')),
            ];
            $header['laba'] = $header['total_jual'] - $header['total_beli'];

            if ($transaksi) {
                $transaksi->update($header);
                // Cara paling aman untuk edit multi-item: hapus detail lama, tulis ulang
                $transaksi->details()->delete();
            } else {
                $header['status_bayar'] = 'belum';
                $transaksi = Transaksi::create($header);
            }

            $transaksi->details()->createMany($items);

            // Total jual bisa berubah saat edit -> status bayar ikut dihitung ulang
            $transaksi->refreshStatusBayar();

            return $transaksi;
        });
    }

    /** Hitung subtotal tiap baris. Rupiah dibulatkan tanpa desimal. */
    private function hitungItems(array $items): array
    {
        return array_map(function (array $item) {
            $qty = round((float) $item['qty'], 2);
            $hargaBeli = round((float) $item['harga_beli']);
            $hargaJual = round((float) $item['harga_jual']);

            return [
                'barang_id' => (int) $item['barang_id'],
                'supplier_id' => (int) $item['supplier_id'],
                'qty' => $qty,
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'subtotal_beli' => round($qty * $hargaBeli),
                'subtotal_jual' => round($qty * $hargaJual),
            ];
        }, $items);
    }
}
