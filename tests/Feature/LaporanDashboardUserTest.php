<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LaporanDashboardUserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Klien $klien;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => Hash::make('password')]);
        $this->actingAs($this->user);

        $this->klien = Klien::create(['nama' => 'Hotel Puri Wangi']);
        $this->supplier = Supplier::create(['nama' => 'Kelompok Tani Cipanas']);
        $brokoli = Barang::create(['nama' => 'Brokoli', 'satuan' => 'kg', 'harga_beli_default' => 22000, 'harga_jual_default' => 30000]);

        $service = app(TransaksiService::class);
        // Oktober: jual 300.000 / beli 220.000 ; November: jual 150.000 / beli 110.000
        foreach ([['2026-10-20', 10], ['2026-11-02', 5]] as [$tgl, $qty]) {
            $service->simpan(['tanggal' => $tgl, 'klien_id' => $this->klien->id, 'items' => [
                ['barang_id' => $brokoli->id, 'supplier_id' => $this->supplier->id, 'qty' => $qty, 'harga_beli' => 22000, 'harga_jual' => 30000],
            ]]);
        }
        Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'klien', 'klien_id' => $this->klien->id, 'jumlah' => 100000, 'metode' => 'tunai']);
        Pembayaran::create(['tanggal' => '2026-11-03', 'jenis' => 'supplier', 'supplier_id' => $this->supplier->id, 'jumlah' => 200000, 'metode' => 'tunai']);
    }

    public function test_laporan_per_klien_periode_dan_sisa_kumulatif(): void
    {
        $res = $this->get('/laporan/klien?dari=2026-11-01&sampai=2026-11-30')->assertOk();
        $row = $res->viewData('rows')->first();

        $this->assertEquals(150000, $row->total_jual);       // hanya November
        $this->assertEquals(100000, $row->dibayar);
        $this->assertEquals(450000 - 100000, $row->sisa);    // kumulatif s/d 30 Nov
        $res->assertSee('Hotel Puri Wangi');

        // Rincian barang per klien terpilih, hanya periode ini
        $rincian = $this->get("/laporan/klien?dari=2026-11-01&sampai=2026-11-30&klien_id={$this->klien->id}")
            ->assertOk()->assertSee('Rincian Barang')->assertSee('02 Nov 2026')->assertDontSee('20 Okt 2026')
            ->viewData('rincian');
        $this->assertCount(1, $rincian);
        $this->assertCount(1, $rincian->first()->items);
    }

    public function test_laporan_per_supplier(): void
    {
        $row = $this->get('/laporan/supplier?dari=2026-10-01&sampai=2026-11-30')->assertOk()->viewData('rows')->first();
        $this->assertEquals(15, $row->qty);
        $this->assertEquals(330000, $row->total_beli);
        $this->assertEquals(200000, $row->dibayar);
        $this->assertEquals(130000, $row->sisa);

        $this->get("/laporan/supplier?dari=2026-10-01&sampai=2026-11-30&supplier_id={$this->supplier->id}")
            ->assertOk()->assertSee('Barang dari');
    }

    public function test_laporan_per_barang(): void
    {
        $res = $this->get('/laporan/barang?dari=2026-10-01&sampai=2026-11-30')->assertOk();
        $row = $res->viewData('rows')->first();
        $this->assertEquals(15, $row->qty);
        $this->assertEquals(450000, $row->total_jual);
        $this->assertEquals(120000, $row->laba);
    }

    public function test_laporan_multi_klien_supplier_mode_harga_dan_format_qty(): void
    {
        $klien2 = Klien::create(['nama' => 'Resto Melati']);
        $supplier2 = Supplier::create(['nama' => 'Pak Ujang', 'warna' => '#16a34a']);
        $tomat = Barang::create(['nama' => 'Tomat', 'satuan' => 'kg', 'supplier_id' => $supplier2->id, 'harga_beli_default' => 8000, 'harga_jual_default' => 11000]);
        app(TransaksiService::class)->simpan(['tanggal' => '2026-11-05', 'klien_id' => $klien2->id, 'items' => [
            ['barang_id' => $tomat->id, 'supplier_id' => $supplier2->id, 'qty' => 2.5, 'harga_beli' => 8000, 'harga_jual' => 11000],
        ]]);
        $periode = 'dari=2026-11-01&sampai=2026-11-30';

        // Pilih dua klien sekaligus -> dua ringkasan + dua rincian
        $res = $this->get("/laporan/klien?{$periode}&klien_id[]={$this->klien->id}&klien_id[]={$klien2->id}")->assertOk();
        $this->assertCount(2, $res->viewData('rows'));
        $this->assertCount(2, $res->viewData('rincian'));

        // Filter supplier: hanya item dari Pak Ujang yang dihitung
        $rows = $this->get("/laporan/klien?{$periode}&supplier_id[]={$supplier2->id}")->viewData('rows');
        $this->assertSame(['Resto Melati'], $rows->pluck('nama')->all());
        $this->assertEquals(27500, $rows->first()->total_jual);

        // Mode harga jual saja: harga beli tidak tampil
        $this->get("/laporan/klien?{$periode}&klien_id[]={$klien2->id}&harga=jual")
            ->assertSee('Rp 27.500')->assertDontSee('Rp 20.000')->assertDontSee('Harga Beli');
        $this->get("/laporan/klien?{$periode}&klien_id[]={$klien2->id}&harga=lengkap")
            ->assertSee('Rp 20.000')->assertSee('Laba');

        // Format qty
        $this->get("/laporan/barang?{$periode}&barang_id[]={$tomat->id}&qty=satuan")->assertSee('>2,5 kg<', false);
        $this->get("/laporan/barang?{$periode}&barang_id[]={$tomat->id}&qty=desimal")->assertSee('>2,50 kg<', false);
        $this->get("/laporan/barang?{$periode}&barang_id[]={$tomat->id}&qty=angka")->assertSee('>2,5<', false)->assertDontSee('>2,5 kg<', false);

        // Laporan supplier multi pilih + warna supplier
        $res = $this->get("/laporan/supplier?{$periode}&supplier_id[]={$this->supplier->id}&supplier_id[]={$supplier2->id}");
        $this->assertCount(2, $res->viewData('rincian'));
        $res->assertSee('--sw: #16a34a', false);
    }

    public function test_export_pdf_dan_excel(): void
    {
        foreach (['klien', 'supplier', 'barang'] as $jenis) {
            // PDF tidak otomatis tersimpan: default dibuka (inline), unduh=1 untuk menyimpan
            $pdf = $this->get("/laporan/{$jenis}?dari=2026-10-01&sampai=2026-11-30&export=pdf")->assertOk();
            $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
            $this->assertStringStartsWith('inline', $pdf->headers->get('content-disposition'));
            $unduh = $this->get("/laporan/{$jenis}?dari=2026-10-01&sampai=2026-11-30&export=pdf&unduh=1")->assertOk();
            $this->assertStringStartsWith('attachment', $unduh->headers->get('content-disposition'));

            $xls = $this->get("/laporan/{$jenis}?dari=2026-10-01&sampai=2026-11-30&export=excel")->assertOk();
            $this->assertStringContainsString('.xlsx', $xls->headers->get('content-disposition'));
        }

        // Export dengan rincian (1 klien / 1 supplier dipilih)
        $this->get("/laporan/klien?klien_id={$this->klien->id}&export=excel")->assertOk();
        $this->get("/laporan/supplier?supplier_id[]={$this->supplier->id}&harga=lengkap&qty=angka&export=pdf")->assertOk();
        $this->get("/laporan/barang?supplier_id[]={$this->supplier->id}&export=excel")->assertOk();
    }

    public function test_laporan_kosong_menampilkan_empty_state(): void
    {
        $this->get('/laporan/barang?dari=2020-01-01&sampai=2020-01-31')->assertOk()->assertSee('Tidak ada data pada periode ini');
    }

    public function test_dashboard_statistik_dan_top(): void
    {
        $res = $this->get('/dashboard?dari=2026-11-01&sampai=2026-11-30')->assertOk();
        $stat = $res->viewData('stat');

        $this->assertEquals(1, $stat->nota);
        $this->assertEquals(5, $stat->qty);
        $this->assertEquals(150000, $stat->jual);
        $this->assertEquals(110000, $stat->beli);
        $res->assertSee('Top 3 Supplier')->assertSee('Kelompok Tani Cipanas')->assertSee('Brokoli')
            ->assertSee('Hotel Puri Wangi')->assertSee('5 Transaksi Terakhir');
    }

    public function test_dashboard_default_bulan_ini(): void
    {
        $this->get('/dashboard')->assertOk()->assertViewHas('periode', fn ($p) => $p->dariStr() === now()->startOfMonth()->toDateString());
    }

    public function test_ubah_profil(): void
    {
        $this->put('/user', ['name' => 'Admin Sayur', 'email' => 'baru@admin.com'])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Admin Sayur', 'email' => 'baru@admin.com']);
    }

    public function test_ganti_password_wajib_password_lama_benar(): void
    {
        $this->put('/user/password', ['current_password' => 'salah', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSessionHasErrorsIn('password', 'current_password');

        $this->put('/user/password', ['current_password' => 'password', 'password' => 'rahasia123', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrorsIn('password', 'password');

        $this->put('/user/password', ['current_password' => 'password', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSessionHas('success');
        $this->assertTrue(Hash::check('rahasia123', $this->user->fresh()->password));
    }
}
