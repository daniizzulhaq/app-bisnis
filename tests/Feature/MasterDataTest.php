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
        $this->assertSoftDeleted($supplier); // masuk Riwayat Hapus
    }

    public function test_supplier_punya_warna(): void
    {
        // Tanpa warna -> otomatis warna preset pertama yang belum dipakai
        $this->post('/supplier', ['nama' => 'Pak A'])->assertRedirect('/supplier');
        $this->post('/supplier', ['nama' => 'Pak B'])->assertRedirect('/supplier');
        $this->assertSame([Supplier::WARNA[0], Supplier::WARNA[1]], Supplier::orderBy('id')->pluck('warna')->all());

        $this->post('/supplier', ['nama' => 'Pak C', 'warna' => '#ABCDEF'])->assertRedirect('/supplier');
        $this->assertDatabaseHas('suppliers', ['nama' => 'Pak C', 'warna' => '#abcdef']);
        $this->post('/supplier', ['nama' => 'Pak D', 'warna' => 'merah'])->assertSessionHasErrors('warna');

        // Edit tanpa mengirim warna -> warna lama dipertahankan
        $c = Supplier::firstWhere('nama', 'Pak C');
        $this->put("/supplier/{$c->id}", ['nama' => 'Pak C2'])->assertRedirect('/supplier');
        $this->assertSame('#abcdef', $c->fresh()->warna);

        $this->get('/supplier')->assertSee('--sw: #abcdef', false);
        $this->get('/supplier/create')->assertOk()->assertSee('value="'.Supplier::WARNA[2].'"', false);
    }

    public function test_barang_dengan_supplier_dan_klasifikasi(): void
    {
        $darto = Supplier::create(['nama' => 'Pak Darto', 'warna' => '#16a34a']);
        $sari = Supplier::create(['nama' => 'Bu Sari']);

        $this->get('/barang/create?supplier='.$darto->id)->assertOk()
            ->assertSee('<option value="'.$darto->id.'" data-warna="#16a34a"', false);

        $this->post('/barang', ['supplier_id' => $darto->id, 'nama' => 'Wortel', 'satuan' => 'kg',
            'harga_beli_default' => '9.000', 'harga_jual_default' => '12.000'])->assertRedirect('/barang');
        $this->post('/barang', ['supplier_id' => $sari->id, 'nama' => 'Bayam', 'satuan' => 'ikat',
            'harga_beli_default' => '2.000', 'harga_jual_default' => '3.000'])->assertRedirect('/barang');
        $this->post('/barang', ['nama' => 'Kol', 'satuan' => 'kg',
            'harga_beli_default' => '5.000', 'harga_jual_default' => '7.000'])->assertRedirect('/barang');
        $this->post('/barang', ['supplier_id' => 999, 'nama' => 'X', 'satuan' => 'kg',
            'harga_beli_default' => 1, 'harga_jual_default' => 1])->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseHas('barangs', ['nama' => 'Wortel', 'supplier_id' => $darto->id]);

        // Klasifikasi: semua (dikelompokkan), per supplier, tanpa supplier
        $this->get('/barang')->assertOk()->assertSeeInOrder(['Bu Sari', 'Bayam', 'Pak Darto', 'Wortel', 'Tanpa supplier', 'Kol']);
        $this->get('/barang?supplier='.$darto->id)->assertSee('Wortel')->assertDontSee('Bayam');
        $this->get('/barang?supplier=tanpa')->assertSee('Kol')->assertDontSee('Wortel');

        // Supplier dihapus -> barang tetap ada, tampil sebagai tanpa supplier
        $this->delete("/supplier/{$sari->id}")->assertRedirect('/supplier');
        $this->assertDatabaseHas('barangs', ['nama' => 'Bayam', 'deleted_at' => null]);
        $this->get('/barang?supplier=tanpa')->assertSee('Bayam')->assertSee('Kol');
        // Pencarian di halaman barang (join supplier) tidak error kolom ambigu
        $this->get('/barang?q=bayam')->assertOk()->assertSee('Bayam')->assertDontSee('>Kol<', false);
        $this->get('/barang?q=bayam&supplier=tanpa')->assertOk()->assertSee('Bayam');
        $this->get('/supplier?q=sari')->assertOk();
        $this->get('/klien?q=hotel')->assertOk();
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
