<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Produksi: hanya akun admin untuk login.
     *
     * Data contoh (demo) bisa diisi manual bila perlu:
     *   php artisan db:seed --class=MasterSeeder
     *   php artisan db:seed --class=TransaksiSeeder
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
    }
}
