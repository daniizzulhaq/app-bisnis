<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            MasterSeeder::class,     // supplier sayur, barang, hotel & resto
            TransaksiSeeder::class,  // nota, pembayaran, cashflow ~3 bulan terakhir
        ]);
    }
}
