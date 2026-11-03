<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Cashflow;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Illuminate\Database\Seeder;

/**
 * Transaksi dummy ~3 bulan terakhir + pembayaran + cashflow manual.
 * Memakai TransaksiService & model Pembayaran agar total, status bayar,
 * dan cashflow terbentuk lewat jalur yang sama dengan aplikasi.
 */
class TransaksiSeeder extends Seeder
{
    public function run(TransaksiService $service): void
    {
        if (Transaksi::exists()) {
            $this->command?->warn('Transaksi sudah ada, TransaksiSeeder dilewati.');

            return;
        }

        mt_srand(2026); // hasil acak yang sama setiap seeding

        $kliens = Klien::all();
        $suppliers = Supplier::all();
        $barangs = Barang::all();
        $mulai = now()->subMonthsNoOverflow(2)->startOfMonth();

        // Modal awal & biaya operasional rutin
        Cashflow::create(['tanggal' => $mulai, 'tipe' => 'masuk', 'kategori' => 'Modal', 'nominal' => 15000000, 'keterangan' => 'Modal awal usaha']);

        for ($tgl = $mulai->copy(); $tgl->lte(now()); $tgl->addDay()) {
            // Rata-rata 1-2 nota per hari, kadang libur
            $jumlahNota = [0, 1, 1, 2, 2, 3][mt_rand(0, 5)];

            for ($n = 0; $n < $jumlahNota; $n++) {
                $items = [];
                foreach ($barangs->random(mt_rand(3, 6)) as $barang) {
                    $qty = $barang->satuan === 'kg' ? mt_rand(5, 60) / ($barang->harga_beli_default > 25000 ? 4 : 1) : mt_rand(10, 50);
                    $items[] = [
                        'barang_id' => $barang->id,
                        'supplier_id' => $suppliers->random()->id,
                        'qty' => $qty,
                        // Harga sedikit berfluktuasi di sekitar harga default
                        'harga_beli' => round($barang->harga_beli_default * mt_rand(90, 110) / 100, -2),
                        'harga_jual' => round($barang->harga_jual_default * mt_rand(95, 108) / 100, -2),
                    ];
                }

                $nota = $service->simpan([
                    'tanggal' => $tgl->toDateString(),
                    'klien_id' => $kliens->random()->id,
                    'keterangan' => mt_rand(0, 4) === 0 ? 'Kirim pagi sebelum jam 06.00' : null,
                    'items' => $items,
                ]);

                // Pola pembayaran klien: nota lama mayoritas lunas, nota baru banyak yang belum
                $umurHari = $tgl->diffInDays(now());
                $peluang = mt_rand(1, 100);
                if ($umurHari > 7 && $peluang <= 75 || $umurHari <= 7 && $peluang <= 25) {
                    $this->bayarKlien($nota, (float) $nota->total_jual, $tgl->copy()->addDays(mt_rand(0, 7)));
                } elseif ($peluang <= 90) {
                    $this->bayarKlien($nota, round($nota->total_jual * mt_rand(30, 70) / 100, -3), $tgl->copy()->addDays(mt_rand(0, 5)));
                }
            }

            // Biaya operasional
            if ($tgl->isMonday()) {
                Cashflow::create(['tanggal' => $tgl, 'tipe' => 'keluar', 'kategori' => 'Transportasi',
                    'nominal' => mt_rand(3, 6) * 100000, 'keterangan' => 'BBM & tol mobil box mingguan']);
            }
            if ($tgl->day === 1) {
                Cashflow::create(['tanggal' => $tgl, 'tipe' => 'keluar', 'kategori' => 'Gaji',
                    'nominal' => 4500000, 'keterangan' => 'Gaji 2 kurir & 1 helper']);
            }

            // Bayar supplier setiap Sabtu sebesar ~80% belanja minggu itu
            if ($tgl->isSaturday()) {
                $this->bayarSupplierMingguan($tgl);
            }
        }
    }

    private function bayarKlien(Transaksi $nota, float $jumlah, $tanggal): void
    {
        if ($jumlah <= 0) {
            return;
        }

        Pembayaran::create([
            'tanggal' => $tanggal->min(now()),
            'jenis' => 'klien',
            'klien_id' => $nota->klien_id,
            'transaksi_id' => $nota->id,
            'jumlah' => $jumlah,
            'metode' => mt_rand(0, 2) ? 'transfer' : 'tunai',
        ]);
    }

    private function bayarSupplierMingguan($sabtu): void
    {
        $belanja = \DB::table('transaksi_details as d')
            ->join('transaksis as t', 't.id', '=', 'd.transaksi_id')
            ->whereBetween('t.tanggal', [$sabtu->copy()->subDays(6)->toDateString(), $sabtu->toDateString()])
            ->groupBy('d.supplier_id')
            ->selectRaw('d.supplier_id, SUM(d.subtotal_beli) AS total')
            ->pluck('total', 'supplier_id');

        foreach ($belanja as $supplierId => $total) {
            Pembayaran::create([
                'tanggal' => $sabtu,
                'jenis' => 'supplier',
                'supplier_id' => $supplierId,
                'jumlah' => round($total * mt_rand(70, 95) / 100, -3),
                'metode' => mt_rand(0, 1) ? 'transfer' : 'tunai',
                'keterangan' => 'Pembayaran belanja mingguan',
            ]);
        }
    }
}
