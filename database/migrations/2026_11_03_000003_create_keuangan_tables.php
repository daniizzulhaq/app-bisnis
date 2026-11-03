<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran (piutang klien / hutang supplier) dan buku kas (cashflows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->enum('jenis', ['klien', 'supplier'])->index();
            $table->foreignId('klien_id')->nullable()->constrained('kliens')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->restrictOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->enum('metode', ['tunai', 'transfer'])->default('tunai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes(); // riwayat hapus (bisa dipulihkan)
        });

        Schema::create('cashflows', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->enum('tipe', ['masuk', 'keluar'])->index();
            $table->string('kategori', 50);
            $table->decimal('nominal', 15, 2);
            // 1 pembayaran = 1 baris cashflow (dikelola otomatis oleh PembayaranObserver)
            $table->foreignId('pembayaran_id')->nullable()->unique()->constrained('pembayarans')->cascadeOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes(); // riwayat hapus (bisa dipulihkan)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflows');
        Schema::dropIfExists('pembayarans');
    }
};
