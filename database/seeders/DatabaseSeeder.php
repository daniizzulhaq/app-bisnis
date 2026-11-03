<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Hanya membuat akun admin untuk login (tanpa data contoh).
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
    }
}
