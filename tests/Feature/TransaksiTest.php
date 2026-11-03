<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiTest extends TestCase
{
    use RefreshDatabase;

    private Klien $klien;

    private Supplier $s1;

    private Supplier $s2;

    private Barang $wortel;

    private Barang $bayam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->klien = Klien::create(['nama' => 'Hotel Grand Asri']);
        $this->s1 = Supplier::create(['nama' => 'Pak Darto']);
        $this->s2 = Supplier::create(['nama' => 'Bu Sari']);
        $this->wortel = Barang::create(['nama' => 'Wortel', 'satuan' => 'kg', 'harga_beli_default' => 9000, 'harga_jual_default' => 12000]);
        $this->bayam = Barang::create(['nama' => 'Bayam', 'satuan' => 'ikat', 'harga_beli_default' => 2500, 'harga_jual_default' => 4000]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'tanggal' => '2026-11-03',
            'klien_id' => $this->klien->id,
            'items' => [
                // 1 nota bisa berisi barang dari beberapa supplier
                ['barang_id' => $this->wortel->id, 'supplier_id' => $this->s1->id, 'qty' => '12,5', 'harga_beli' => '9.000', 'harga_jual' => '12.000'],
                ['barang_id' => $this->bayam->id, 'supplier_id' => $this->s2->id, 'qty' => '20', 'harga_beli' => '2.500', 'harga_jual' => '4.000'],
                ['barang_id' => '', 'supplier_id' => '', 'qty' => '', 'harga_beli' => '', 'harga_jual' => ''], // baris kosong diabaikan
            ],
        ], $override);
    }

    public function test_simpan_nota_total_dihitung_ulang_di_server(): void
    {
        $data = $this->payload();
        // Nilai palsu dari client harus diabaikan
        $data['total_jual'] = 1;
        $data['items'][0]['subtotal_jual'] = 1;

        $res = $this->post('/rekapan', $data);
        $nota = Transaksi::firstOrFail();
        $res->assertRedirect("/rekapan/{$nota->id}");

        $this->assertSame('NT-2611-0001', $nota->no_nota);
        $this->assertEquals(32.5, $nota->total_qty);
        $this->assertEquals(12.5 * 9000 + 20 * 2500, $nota->total_beli);   // 162.500
        $this->assertEquals(12.5 * 12000 + 20 * 4000, $nota->total_jual);  // 230.000
        $this->assertEquals(67500, $nota->laba);
        $this->assertSame('belum', $nota->status_bayar);
        $this->assertCount(2, $nota->details);
    }

    public function test_nomor_nota_berurutan_per_bulan(): void
    {
        $this->post('/rekapan', $this->payload());
        $this->post('/rekapan', $this->payload());
        $this->post('/rekapan', $this->payload(['tanggal' => '2026-12-01']));

        $this->assertEquals(['NT-2611-0001', 'NT-2611-0002', 'NT-2612-0001'], Transaksi::orderBy('id')->pluck('no_nota')->all());
    }

    public function test_validasi_item(): void
    {
        $this->post('/rekapan', ['tanggal' => '2026-11-03', 'klien_id' => $this->klien->id, 'items' => []])
            ->assertSessionHasErrors('items');

        $data = $this->payload();
        $data['items'][0]['qty'] = '0';
        $this->post('/rekapan', $data)->assertSessionHasErrors(['items.0.qty' => 'Qty harus lebih dari 0.']);
    }

    public function test_halaman_rekapan_filter_detail_edit_cetak(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::first();

        $this->get('/rekapan')->assertOk()->assertSee($nota->no_nota)->assertSee('Rp 230.000');
        $this->get("/rekapan?supplier_id={$this->s2->id}")->assertSee($nota->no_nota);
        $this->get('/rekapan?dari=2026-12-01')->assertDontSee($nota->no_nota)->assertSee('Tidak ada nota yang cocok');
        $this->get("/rekapan/{$nota->id}")->assertOk()->assertSee('Pak Darto')->assertSee('Bu Sari');
        $this->get("/rekapan/{$nota->id}/edit")->assertOk()->assertSee('12.5', false);

        // Nota untuk klien tidak boleh menampilkan harga beli
        $this->get("/rekapan/{$nota->id}/cetak")->assertOk()->assertSee('Rp 230.000')->assertDontSee('Rp 9.000');
        $pdf = $this->get("/rekapan/{$nota->id}/cetak?pdf=1");
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_edit_nota_mengganti_detail_dan_status_bayar(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::first();
        Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $nota->id, 'jumlah' => 100000, 'metode' => 'tunai']);
        $this->assertSame('sebagian', $nota->fresh()->status_bayar);

        // Total dikurangi menjadi 1 item = 8 x 12.000 = 96.000 -> sudah lunas
        $this->put("/rekapan/{$nota->id}", $this->payload(['items' => [
            ['barang_id' => $this->wortel->id, 'supplier_id' => $this->s1->id, 'qty' => 8, 'harga_beli' => 9000, 'harga_jual' => 12000],
        ]]))->assertRedirect("/rekapan/{$nota->id}");

        $nota->refresh();
        $this->assertSame('NT-2611-0001', $nota->no_nota);
        $this->assertCount(1, $nota->details);
        $this->assertEquals(96000, $nota->total_jual);
        $this->assertSame('lunas', $nota->status_bayar);
    }

    public function test_nota_dengan_pembayaran_tidak_bisa_dihapus(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::first();
        Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $nota->id, 'jumlah' => 1000, 'metode' => 'tunai']);

        $this->delete("/rekapan/{$nota->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('transaksis', ['id' => $nota->id]);
    }

    public function test_hapus_nota_menghapus_detail(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::first();

        $this->delete("/rekapan/{$nota->id}")->assertRedirect('/rekapan');
        $this->assertDatabaseCount('transaksis', 0);
        $this->assertDatabaseCount('transaksi_details', 0);
    }
}
