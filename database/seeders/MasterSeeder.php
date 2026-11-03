<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/** Data master contoh: petani/pemasok sayur, daftar sayur, hotel & resto. */
class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['Pak Darto', '0812 2233 4455', 'Pasar Induk Caringin Blok C-12, Bandung', 'Spesialis wortel, kentang, kol'],
            ['Bu Sari Tani Lembang', '0813 9988 7766', 'Jl. Raya Lembang No. 45, Bandung Barat', 'Sayur daun & selada hidroponik'],
            ['Kelompok Tani Cipanas', '0857 1122 3344', 'Desa Sindanglaya, Cipanas, Cianjur', 'Brokoli, paprika, buncis'],
            ['Pak Ujang Bumbu', '0878 5566 7788', 'Pasar Kosambi Los B-7, Bandung', 'Cabai, bawang, bumbu dapur'],
            ['Koperasi Tani Makmur', '0821 4455 6677', 'Pangalengan, Kab. Bandung', 'Tomat, timun, jamur'],
        ];
        foreach ($suppliers as [$nama, $hp, $alamat, $ket]) {
            Supplier::firstOrCreate(['nama' => $nama], ['no_hp' => $hp, 'alamat' => $alamat, 'keterangan' => $ket]);
        }

        // [nama, satuan, harga beli, harga jual]
        $barangs = [
            ['Wortel', 'kg', 9000, 12000],
            ['Kentang Granola', 'kg', 12000, 16000],
            ['Kol / Kubis', 'kg', 5000, 7500],
            ['Tomat Merah', 'kg', 10000, 14000],
            ['Bayam', 'ikat', 2500, 4000],
            ['Kangkung', 'ikat', 2000, 3500],
            ['Brokoli', 'kg', 22000, 30000],
            ['Selada Keriting', 'kg', 18000, 25000],
            ['Cabai Merah Keriting', 'kg', 40000, 52000],
            ['Bawang Merah', 'kg', 32000, 40000],
            ['Bawang Putih', 'kg', 30000, 38000],
            ['Daun Bawang', 'kg', 15000, 20000],
            ['Timun', 'kg', 6000, 9000],
            ['Buncis', 'kg', 12000, 16000],
            ['Jamur Kancing', 'pack', 9000, 13000],
            ['Paprika Merah', 'kg', 45000, 60000],
        ];
        foreach ($barangs as [$nama, $satuan, $beli, $jual]) {
            Barang::firstOrCreate(['nama' => $nama], [
                'satuan' => $satuan, 'harga_beli_default' => $beli, 'harga_jual_default' => $jual,
            ]);
        }

        $kliens = [
            ['Hotel Grand Asri', '022 420 1234', 'Jl. Asia Afrika No. 88, Bandung', 'Kirim jam 05.00, bagian receiving'],
            ['Hotel Puri Wangi', '022 250 5678', 'Jl. Dago No. 120, Bandung', 'Tagihan mingguan'],
            ['Resto Saung Bambu', '0812 7788 9900', 'Jl. Setiabudi No. 210, Bandung', 'Masakan Sunda'],
            ['Kafe Daun Hijau', '0813 1212 3434', 'Jl. Braga No. 15, Bandung', 'Butuh selada & tomat tiap hari'],
            ['Resto Bahari Seafood', '0822 5656 7878', 'Jl. Soekarno-Hatta No. 501, Bandung', null],
            ['Hotel Cemara Indah', '022 731 9090', 'Jl. Cihampelas No. 77, Bandung', 'Pembayaran via transfer'],
        ];
        foreach ($kliens as [$nama, $hp, $alamat, $ket]) {
            Klien::firstOrCreate(['nama' => $nama], ['no_hp' => $hp, 'alamat' => $alamat, 'keterangan' => $ket]);
        }
    }
}
