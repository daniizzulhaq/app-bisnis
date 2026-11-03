<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\CashflowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KlienController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Semua halaman aplikasi wajib login
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Master data
    Route::resource('supplier', SupplierController::class)->except('show');
    Route::resource('barang', BarangController::class)->except('show');
    Route::resource('klien', KlienController::class)->except('show');

    // Rekapan (transaksi / nota)
    Route::get('rekapan/{transaksi}/cetak', [TransaksiController::class, 'cetak'])->name('rekapan.cetak');
    Route::resource('rekapan', TransaksiController::class)->parameters(['rekapan' => 'transaksi']);

    // Keuangan
    Route::get('pembayaran/nota', [PembayaranController::class, 'notaOptions'])->name('pembayaran.nota');
    Route::resource('pembayaran', PembayaranController::class)->except('show');
    Route::resource('cashflow', CashflowController::class)->except('show');

    // Laporan (tambahkan ?export=pdf / ?export=excel)
    Route::prefix('laporan')->name('laporan.')->controller(LaporanController::class)->group(function () {
        Route::get('klien', 'klien')->name('klien');
        Route::get('supplier', 'supplier')->name('supplier');
        Route::get('barang', 'barang')->name('barang');
    });

    // Pengaturan akun admin
    Route::get('user', [UserController::class, 'edit'])->name('user.edit');
    Route::put('user', [UserController::class, 'update'])->name('user.update');
    Route::put('user/password', [UserController::class, 'password'])->name('user.password');
});

require __DIR__.'/auth.php';
