<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_halaman_master_kosong_menampilkan_empty_state(): void
    {
        $this->get('/supplier')->assertOk()->assertSee('Belum ada supplier');
        $this->get('/barang')->assertOk()->assertSee('Belum ada barang');
        $this->get('/klien')->assertOk()->assertSee('Belum ada klien');
    }

    public function test_crud_supplier(): void
    {
        $this->post('/supplier', ['nama' => 'Pak Darto', 'no_hp' => '0812 345'])
            ->assertRedirect('/supplier')->assertSessionHas('success');
        $supplier = Supplier::firstWhere('nama', 'Pak Darto');

        $this->get('/supplier?q=Darto')->assertSee('Pak Darto');
        $this->get("/supplier/{$supplier->id}/edit")->assertOk()->assertSee('Pak Darto');

        $this->put("/supplier/{$supplier->id}", ['nama' => 'Pak Darto Baru'])->assertRedirect('/supplier');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'nama' => 'Pak Darto Baru']);

        $this->delete("/supplier/{$supplier->id}")->assertRedirect('/supplier');
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_validasi_bahasa_indonesia(): void
    {
        $this->post('/klien', ['nama' => '', 'no_hp' => 'abc'])
            ->assertSessionHasErrors(['nama' => 'Nama wajib diisi.', 'no_hp']);
    }

    public function test_harga_barang_berformat_ribuan_dinormalisasi(): void
    {
        $this->post('/barang', [
            'nama' => 'Wortel', 'satuan' => 'KG',
            'harga_beli_default' => '9.000', 'harga_jual_default' => '12.500',
        ])->assertRedirect('/barang');

        $this->assertDatabaseHas('barangs', [
            'nama' => 'Wortel', 'satuan' => 'kg', 'harga_beli_default' => 9000, 'harga_jual_default' => 12500,
        ]);
    }

    public function test_master_yang_dipakai_transaksi_tidak_bisa_dihapus(): void
    {
        $klien = Klien::create(['nama' => 'Hotel A']);
        $supplier = Supplier::create(['nama' => 'Pak B']);
        $barang = Barang::create(['nama' => 'Kol', 'satuan' => 'kg', 'harga_beli_default' => 5000, 'harga_jual_default' => 7000]);

        app(TransaksiService::class)->simpan([
            'tanggal' => now()->toDateString(), 'klien_id' => $klien->id,
            'items' => [['barang_id' => $barang->id, 'supplier_id' => $supplier->id, 'qty' => 2, 'harga_beli' => 5000, 'harga_jual' => 7000]],
        ]);

        $this->from('/klien')->delete("/klien/{$klien->id}")
            ->assertRedirect('/klien')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'tidak dapat dihapus'));
        $this->delete("/supplier/{$supplier->id}")->assertSessionHas('error');
        $this->delete("/barang/{$barang->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('kliens', ['id' => $klien->id]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('barangs', ['id' => $barang->id]);
    }
}
