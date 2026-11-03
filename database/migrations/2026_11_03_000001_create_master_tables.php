<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master: supplier (dengan warna), barang (milik supplier), klien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('warna', 7)->default('#4f6ef7'); // penanda warna supplier (hex)
            $table->timestamps();
            $table->softDeletes(); // riwayat hapus (bisa dipulihkan)

            $table->index('nama');
        });

        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            // Supplier langganan barang ini (klasifikasi). Supplier dihapus -> barang tetap ada.
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('nama', 100);
            $table->string('satuan', 20);
            $table->decimal('harga_beli_default', 15, 2)->default(0);
            $table->decimal('harga_jual_default', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes(); // riwayat hapus (bisa dipulihkan)

            $table->index('nama');
        });

        Schema::create('kliens', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes(); // riwayat hapus (bisa dipulihkan)

            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kliens');
        Schema::dropIfExists('barangs');
        Schema::dropIfExists('suppliers');
    }
};
