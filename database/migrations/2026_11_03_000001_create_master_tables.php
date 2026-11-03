<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master: supplier, barang, klien.
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
            $table->timestamps();

            $table->index('nama');
        });

        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('satuan', 20);
            $table->decimal('harga_beli_default', 15, 2)->default(0);
            $table->decimal('harga_jual_default', 15, 2)->default(0);
            $table->timestamps();

            $table->index('nama');
        });

        Schema::create('kliens', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

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
