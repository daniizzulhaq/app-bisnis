<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cashflow;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatHapusTest extends TestCase
{
    use RefreshDatabase;

    private Klien $klien;

    private Supplier $supplier;

    private Barang $barang;

    private Transaksi $nota;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->klien = Klien::create(['nama' => 'Hotel Mawar']);
        $this->supplier = Supplier::create(['nama' => 'Pak Darto']);
        $this->barang = Barang::create(['nama' => 'Wortel', 'satuan' => 'kg', 'supplier_id' => $this->supplier->id,
            'harga_beli_default' => 9000, 'harga_jual_default' => 12000]);
        // Total jual 120.000
        $this->nota = app(TransaksiService::class)->simpan(['tanggal' => '2026-11-03', 'klien_id' => $this->klien->id, 'items' => [
            ['barang_id' => $this->barang->id, 'supplier_id' => $this->supplier->id, 'qty' => 10, 'harga_beli' => 9000, 'harga_jual' => 12000],
        ]]);
    }

    public function test_nota_terhapus_bisa_dipulihkan_dan_tidak_masuk_laporan(): void
    {
        $this->delete("/rekapan/{$this->nota->id}")->assertSessionHas('success');

        $this->get('/riwayat-hapus?jenis=nota')->assertOk()
            ->assertSee('Nota Hotel Mawar · 03 Nov 2026')->assertSee('Pulihkan')->assertSee('Hapus Permanen');
        $this->assertCount(0, $this->get('/laporan/klien?dari=2026-11-01&sampai=2026-11-30')->viewData('rows'));
        $this->assertCount(0, $this->get('/laporan/barang?dari=2026-11-01&sampai=2026-11-30')->viewData('rows'));
        $this->assertEquals(0, $this->get('/dashboard?dari=2026-11-01&sampai=2026-11-30')->viewData('stat')->nota);

        $this->post("/riwayat-hapus/nota/{$this->nota->id}/pulihkan")->assertSessionHas('success');
        $this->assertNotSoftDeleted($this->nota);
        $this->assertCount(1, $this->nota->fresh()->details);
        $this->assertEquals(120000, $this->get('/laporan/klien?dari=2026-11-01&sampai=2026-11-30')->viewData('rows')->first()->total_jual);
    }

    public function test_pembayaran_terhapus_cashflow_dan_status_ikut_kembali(): void
    {
        $bayar = Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $this->nota->id, 'jumlah' => 120000, 'metode' => 'tunai']);
        $this->assertSame('lunas', $this->nota->fresh()->status_bayar);

        $this->delete("/pembayaran/{$bayar->id}")->assertSessionHas('success');
        $this->assertSoftDeleted($bayar);
        $this->assertSame('belum', $this->nota->fresh()->status_bayar);
        $this->assertDatabaseCount('cashflows', 0);
        // Cashflow otomatis tidak muncul terpisah di Riwayat Hapus
        $this->get('/riwayat-hapus?jenis=cashflow')->assertSee('Tidak ada cashflow yang terhapus');

        $this->post("/riwayat-hapus/pembayaran/{$bayar->id}/pulihkan")->assertSessionHas('success');
        $this->assertSame('lunas', $this->nota->fresh()->status_bayar);
        $this->assertEquals(120000, Cashflow::firstOrFail()->nominal);
    }

    public function test_pulihkan_ditolak_bila_data_induk_masih_terhapus_atau_kelebihan_bayar(): void
    {
        $bayar = Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $this->nota->id, 'jumlah' => 100000, 'metode' => 'tunai']);
        $bayar->delete();
        $this->nota->delete();

        // Nota masih terhapus -> pembayaran tidak bisa dipulihkan
        $this->post("/riwayat-hapus/pembayaran/{$bayar->id}/pulihkan")->assertSessionHas('error');
        $this->assertSoftDeleted($bayar);

        // Klien ikut dihapus -> nota tidak bisa dipulihkan sebelum klien dipulihkan
        $this->delete("/klien/{$this->klien->id}")->assertSessionHas('success');
        $this->post("/riwayat-hapus/nota/{$this->nota->id}/pulihkan")->assertSessionHas('error');
        $this->post("/riwayat-hapus/klien/{$this->klien->id}/pulihkan")->assertSessionHas('success');
        $this->post("/riwayat-hapus/nota/{$this->nota->id}/pulihkan")->assertSessionHas('success');

        // Sudah ada pembayaran baru yang melunasi -> pembayaran lama tidak boleh dipulihkan (kelebihan bayar)
        Pembayaran::create(['tanggal' => '2026-11-04', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $this->nota->id, 'jumlah' => 120000, 'metode' => 'tunai']);
        $this->post("/riwayat-hapus/pembayaran/{$bayar->id}/pulihkan")->assertSessionHas('error');

        // Hapus permanen klien yang masih punya data -> ditolak dengan pesan, bukan error 500
        $k = Klien::create(['nama' => 'Resto X']);
        $n = app(TransaksiService::class)->simpan(['tanggal' => '2026-11-05', 'klien_id' => $k->id, 'items' => [
            ['barang_id' => $this->barang->id, 'supplier_id' => $this->supplier->id, 'qty' => 1, 'harga_beli' => 1, 'harga_jual' => 1],
        ]]);
        $n->delete();
        $k->delete();
        $this->delete("/riwayat-hapus/klien/{$k->id}")->assertSessionHas('error');
        $this->assertSoftDeleted($k);
    }

    public function test_data_terhapus_tidak_bisa_dipilih_dan_master_kembali_normal(): void
    {
        $sup2 = Supplier::create(['nama' => 'Bu Sari']);
        $this->delete("/supplier/{$sup2->id}")->assertSessionHas('success');

        $this->get('/riwayat-hapus?jenis=supplier')->assertSee('Bu Sari');
        $this->get('/rekapan/create')->assertDontSee('Bu Sari');
        $this->post('/rekapan', ['tanggal' => '2026-11-03', 'klien_id' => $this->klien->id, 'items' => [
            ['barang_id' => $this->barang->id, 'supplier_id' => $sup2->id, 'qty' => 1, 'harga_beli' => 1, 'harga_jual' => 1],
        ]])->assertSessionHasErrors('items.0.supplier_id');

        $this->post("/riwayat-hapus/supplier/{$sup2->id}/pulihkan")->assertSessionHas('success');
        $this->get('/supplier')->assertSee('Bu Sari');

        // Hapus permanen supplier yang tidak dipakai
        $sup2->delete();
        $this->delete("/riwayat-hapus/supplier/{$sup2->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('suppliers', ['id' => $sup2->id]);

        // Jenis tidak dikenal / data yang tidak terhapus -> 404
        $this->post('/riwayat-hapus/rahasia/1/pulihkan')->assertNotFound();
        $this->post("/riwayat-hapus/barang/{$this->barang->id}/pulihkan")->assertNotFound();
    }
}
