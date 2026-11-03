<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Akun admin tunggal. updateOrCreate agar aman dijalankan berulang kali.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
    }
}
