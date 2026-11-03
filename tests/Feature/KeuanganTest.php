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

class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    private Klien $klien;

    private Supplier $supplier;

    private Transaksi $nota;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->klien = Klien::create(['nama' => 'Resto Saung Bambu']);
        $this->supplier = Supplier::create(['nama' => 'Pak Ujang']);
        $barang = Barang::create(['nama' => 'Cabai', 'satuan' => 'kg', 'harga_beli_default' => 40000, 'harga_jual_default' => 50000]);

        // Total jual 500.000, total beli 400.000
        $this->nota = app(TransaksiService::class)->simpan([
            'tanggal' => '2026-11-02', 'klien_id' => $this->klien->id,
            'items' => [['barang_id' => $barang->id, 'supplier_id' => $this->supplier->id, 'qty' => 10, 'harga_beli' => 40000, 'harga_jual' => 50000]],
        ]);
    }

    private function bayarKlien(string $jumlah, array $extra = [])
    {
        return $this->post('/pembayaran', array_merge([
            'tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $this->nota->id, 'jumlah' => $jumlah, 'metode' => 'transfer',
        ], $extra));
    }

    public function test_pembayaran_klien_membuat_cashflow_masuk_dan_ubah_status(): void
    {
        $this->bayarKlien('200.000')->assertRedirect('/pembayaran');

        $this->assertSame('sebagian', $this->nota->fresh()->status_bayar);
        $cf = Cashflow::firstOrFail();
        $this->assertSame('masuk', $cf->tipe);
        $this->assertEquals(200000, $cf->nominal);
        $this->assertSame(Cashflow::KATEGORI_KLIEN, $cf->kategori);
        $this->assertStringContainsString($this->nota->no_nota, $cf->keterangan);

        $this->bayarKlien('300.000');
        $this->assertSame('lunas', $this->nota->fresh()->status_bayar);
        $this->assertDatabaseCount('cashflows', 2);
    }

    public function test_tidak_boleh_bayar_melebihi_sisa_tagihan(): void
    {
        $this->bayarKlien('600.000')->assertSessionHasErrors('jumlah');
        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_nota_harus_milik_klien_yang_dipilih(): void
    {
        $lain = Klien::create(['nama' => 'Hotel Lain']);
        $this->bayarKlien('100.000', ['klien_id' => $lain->id])->assertSessionHasErrors('transaksi_id');
    }

    public function test_edit_dan_hapus_pembayaran_ikut_memperbarui_cashflow(): void
    {
        $this->bayarKlien('500.000');
        $p = Pembayaran::firstOrFail();
        $this->assertSame('lunas', $this->nota->fresh()->status_bayar);

        // Saat edit, pembayaran sendiri tidak dihitung -> 500.000 tetap valid
        $this->put("/pembayaran/{$p->id}", [
            'tanggal' => '2026-11-04', 'jenis' => 'klien', 'klien_id' => $this->klien->id,
            'transaksi_id' => $this->nota->id, 'jumlah' => '150.000', 'metode' => 'tunai',
        ])->assertRedirect('/pembayaran');

        $cf = Cashflow::firstOrFail();
        $this->assertEquals(150000, $cf->nominal);
        $this->assertSame('2026-11-04', $cf->tanggal->toDateString());
        $this->assertSame('sebagian', $this->nota->fresh()->status_bayar);
        $this->assertDatabaseCount('cashflows', 1);

        $this->delete("/pembayaran/{$p->id}");
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertSame('belum', $this->nota->fresh()->status_bayar);
    }

    public function test_pembayaran_supplier_membuat_cashflow_keluar(): void
    {
        $this->post('/pembayaran', [
            'tanggal' => '2026-11-03', 'jenis' => 'supplier', 'supplier_id' => $this->supplier->id,
            'klien_id' => $this->klien->id, // diabaikan karena jenis supplier
            'transaksi_id' => $this->nota->id, 'jumlah' => '400.000', 'metode' => 'tunai',
        ])->assertRedirect('/pembayaran');

        $p = Pembayaran::firstOrFail();
        $this->assertNull($p->klien_id);
        $this->assertSame('keluar', $p->cashflow->tipe);
        // Pembayaran supplier tidak mengubah status piutang klien
        $this->assertSame('belum', $this->nota->fresh()->status_bayar);
    }

    public function test_dropdown_nota_json(): void
    {
        $this->getJson("/pembayaran/nota?jenis=klien&id={$this->klien->id}")
            ->assertOk()->assertJsonFragment(['value' => $this->nota->id, 'sisa' => 500000]);

        $this->getJson("/pembayaran/nota?jenis=supplier&id={$this->supplier->id}")
            ->assertOk()->assertJsonCount(1);
    }

    public function test_form_pembayaran_prefill_dari_nota(): void
    {
        $this->get("/pembayaran/create?transaksi={$this->nota->id}")
            ->assertOk()->assertSee('value="500.000"', false);
    }

    public function test_cashflow_saldo_awal_masuk_keluar_akhir(): void
    {
        Cashflow::create(['tanggal' => '2026-10-15', 'tipe' => 'masuk', 'kategori' => 'Modal', 'nominal' => 1000000]);
        $this->bayarKlien('500.000'); // 03 Nov masuk
        $this->post('/cashflow', ['tanggal' => '2026-11-05', 'tipe' => 'keluar', 'kategori' => 'Transportasi', 'nominal' => '150.000'])
            ->assertRedirect();

        $res = $this->get('/cashflow?dari=2026-11-01&sampai=2026-11-30')->assertOk();
        $res->assertViewHas('saldoAwal', 1000000.0);
        $res->assertViewHas('totalMasuk', 500000.0);
        $res->assertViewHas('totalKeluar', 150000.0);
        $res->assertViewHas('saldoAkhir', 1350000.0);
        $res->assertSee('Rp 1.350.000');
    }

    public function test_cashflow_otomatis_tidak_bisa_diubah_dari_menu_cashflow(): void
    {
        $this->bayarKlien('100.000');
        $cf = Cashflow::firstOrFail();

        $this->get("/cashflow/{$cf->id}/edit")->assertRedirect('/cashflow')->assertSessionHas('error');
        $this->delete("/cashflow/{$cf->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('cashflows', ['id' => $cf->id]);
    }

    public function test_cashflow_manual_bisa_diedit_dan_dihapus(): void
    {
        $cf = Cashflow::create(['tanggal' => '2026-11-01', 'tipe' => 'keluar', 'kategori' => 'Gaji', 'nominal' => 100]);

        $this->put("/cashflow/{$cf->id}", ['tanggal' => '2026-11-01', 'tipe' => 'keluar', 'kategori' => 'Gaji', 'nominal' => '4.500.000'])
            ->assertRedirect('/cashflow');
        $this->assertEquals(4500000, $cf->fresh()->nominal);

        $this->delete("/cashflow/{$cf->id}");
        $this->assertDatabaseCount('cashflows', 0);
    }
}
