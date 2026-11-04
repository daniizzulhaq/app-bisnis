<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Klien;
use App\Models\Pembayaran;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

        $this->assertEquals(32.5, $nota->total_qty);
        $this->assertEquals(12.5 * 9000 + 20 * 2500, $nota->total_beli);   // 162.500
        $this->assertEquals(12.5 * 12000 + 20 * 4000, $nota->total_jual);  // 230.000
        $this->assertEquals(67500, $nota->laba);
        $this->assertSame('belum', $nota->status_bayar);
        $this->assertCount(2, $nota->details);
    }

    public function test_nota_tanpa_nomor_dan_baris_tambahan_kosong_diabaikan(): void
    {
        $this->assertFalse(Schema::hasColumn('transaksis', 'no_nota'));

        // Baris yang hanya berisi supplier/harga (barang & qty kosong) dianggap kosong
        $data = $this->payload();
        $data['items'][] = ['barang_id' => '', 'supplier_id' => $this->s1->id, 'qty' => '', 'harga_beli' => '9.000', 'harga_jual' => ''];
        $this->post('/rekapan', $data)->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Nota Hotel Grand Asri · 03 Nov 2026 berhasil disimpan.');

        $this->assertCount(2, Transaksi::firstOrFail()->details);
    }

    public function test_form_nota_barang_dikelompokkan_per_supplier(): void
    {
        $this->wortel->update(['supplier_id' => $this->s1->id]);

        $this->get('/rekapan/create')->assertOk()
            ->assertDontSee('No. Nota')
            ->assertSee('id="jumlah-baris"', false)
            ->assertSee('<optgroup label="Pak Darto"', false)
            ->assertSee('<optgroup label="Tanpa supplier"', false)
            ->assertSee('"supplier":'.$this->s1->id, false);
    }

    public function test_validasi_item(): void
    {
        $this->post('/rekapan', ['tanggal' => '2026-11-03', 'klien_id' => $this->klien->id, 'items' => []])
            ->assertSessionHasErrors('items');

        $data = $this->payload();
        $data['items'][0]['qty'] = '0';
        $this->post('/rekapan', $data)->assertSessionHasErrors(['items.0.qty' => 'Qty harus lebih dari 0.']);
    }

    public function test_harga_50_item_tetap_utuh_setelah_qty_gagal_validasi_dan_disimpan_ulang(): void
    {
        $items = array_fill(0, 50, [
            'barang_id' => (string) $this->wortel->id,
            'supplier_id' => (string) $this->s1->id,
            'qty' => '1', 'harga_beli' => '60.000', 'harga_jual' => '64.000',
        ]);
        $items[0]['qty'] = '1,5';
        $items[25]['qty'] = '';
        $data = $this->payload(['items' => $items, 'keterangan' => 'Pesanan 50 item']);

        foreach (['', '0'] as $qtySalah) {
            $data['items'][25]['qty'] = $qtySalah;
            $this->from('/rekapan/create')->post('/rekapan', $data)->assertSessionHasErrors('items.25.qty');
            $this->assertDatabaseCount('transaksis', 0);
            $res = $this->get('/rekapan/create')->assertOk()->assertSee('Pesanan 50 item');
            $restored = $this->itemsDariForm($res->getContent());
            $this->assertCount(50, $restored);
            foreach ($restored as $i => $item) {
                $this->assertSame('64.000', $item['harga_jual'], "Harga jual baris {$i} berubah setelah validasi.");
                $this->assertSame('60.000', $item['harga_beli'], "Harga beli baris {$i} berubah setelah validasi.");
                $this->assertSame($items[$i]['barang_id'], $item['barang_id']);
                $this->assertSame($items[$i]['supplier_id'], $item['supplier_id']);
            }
            $this->assertEquals(1.5, (float) str_replace(',', '.', $restored[0]['qty']));
            $data['items'] = $restored;
        }

        $data['items'][25]['qty'] = '1';
        $this->post('/rekapan', $data)->assertSessionHasNoErrors()->assertRedirect();
        $nota = Transaksi::firstOrFail();
        $this->assertCount(50, $nota->details);
        $this->assertEquals(50.5 * 64000, $nota->total_jual);
        $this->assertEquals(50.5 * 60000, $nota->total_beli);
    }

    public function test_harga_edit_nota_tetap_utuh_setelah_validasi_gagal(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::firstOrFail();
        $data = $this->payload();
        $data['items'][0]['qty'] = '';
        $edit = route('rekapan.edit', $nota);
        $this->from($edit)->put(route('rekapan.update', $nota), $data)->assertSessionHasErrors('items.0.qty');
        $restored = $this->itemsDariForm($this->get($edit)->assertOk()->getContent());
        $this->assertSame('12.000', $restored[0]['harga_jual']);
        $this->assertSame('9.000', $restored[0]['harga_beli']);
        $this->assertSame('4.000', $restored[1]['harga_jual']);
        $this->assertSame('2.500', $restored[1]['harga_beli']);

        $restored[0]['qty'] = '12,5';
        $this->put(route('rekapan.update', $nota), $this->payload(['items' => $restored]))->assertSessionHasNoErrors();
        $this->assertEquals(230000, $nota->fresh()->total_jual);
        $this->assertEquals(162500, $nota->fresh()->total_beli);

        // Nilai dari database tetap diformat sebagai Rupiah saat membuka edit lagi.
        $editItems = $this->itemsDariForm($this->get($edit)->assertOk()->getContent());
        $this->assertSame('12.000', $editItems[0]['harga_jual']);
        $this->assertSame('2.500', $editItems[1]['harga_beli']);
    }

    /** Ambil isian yang benar-benar dirender, seperti dikirim ulang oleh browser. */
    private function itemsDariForm(string $html): array
    {
        $errorsSebelumnya = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($errorsSebelumnya);
        $xpath = new \DOMXPath($dom);
        $items = [];
        foreach ($xpath->query('//tbody[@id="item-body"]//input[@name] | //tbody[@id="item-body"]//select[@name]') as $field) {
            if (! preg_match('/^items\[(\d+)\]\[(\w+)\]$/', $field->getAttribute('name'), $matches)) {
                continue;
            }
            $items[(int) $matches[1]][$matches[2]] = $field->tagName === 'select'
                ? ($xpath->query('.//option[@selected]', $field)->item(0)?->getAttribute('value') ?? '')
                : $field->getAttribute('value');
        }

        return $items;
    }

    public function test_edit_nota_mempertahankan_filter_dan_halaman_rekapan(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::firstOrFail();
        $asal = route('rekapan.index', ['dari' => '2026-11-02', 'sampai' => '2026-11-03', 'klien_id' => $this->klien->id, 'page' => 2]);
        $edit = route('rekapan.edit', [$nota, 'kembali' => $asal]);

        $this->get($edit)->assertOk()->assertSee('name="kembali" value="'.e($asal).'"', false);
        $detail = route('rekapan.show', [$nota, 'kembali' => $asal]);
        $this->put(route('rekapan.update', $nota), $this->payload(['kembali' => $asal]))->assertRedirect($detail);
        $this->get($detail)->assertOk()->assertSee('href="'.e($asal).'"', false);

        $this->get(route('rekapan.edit', [$nota, 'kembali' => 'https://example.com']))
            ->assertViewHas('kembali', route('rekapan.index'));
    }

    public function test_nota_baru_dimulai_dengan_nol_baris_dan_viewport_desktop(): void
    {
        $res = $this->get('/rekapan/create')->assertOk()->assertSee('width=1440, user-scalable=yes', false);
        preg_match('/<tbody id="item-body">(.*?)<\/tbody>/s', $res->getContent(), $matches);
        $this->assertSame('', trim($matches[1]));
    }

    public function test_halaman_rekapan_filter_detail_edit_cetak(): void
    {
        $this->post('/rekapan', $this->payload());
        $nota = Transaksi::first();

        $this->get('/rekapan')->assertOk()->assertSee('03 Nov 2026')->assertSee('Rp 230.000')->assertDontSee('No. Nota');
        $this->get("/rekapan?supplier_id={$this->s2->id}")->assertSee('Rp 230.000');
        $this->get('/rekapan?dari=2026-12-01')->assertDontSee('Rp 230.000')->assertSee('Tidak ada nota yang cocok');
        $this->get("/rekapan/{$nota->id}")->assertOk()->assertSee('Pak Darto')->assertSee('Bu Sari');
        $this->get("/rekapan/{$nota->id}/edit")->assertOk()->assertSee('12.5', false);

        // Nota untuk klien tidak boleh menampilkan harga beli
        $this->get("/rekapan/{$nota->id}/cetak")->assertOk()->assertSee('Rp 230.000')->assertDontSee('Rp 9.000');
        // PDF: default dibuka di browser (inline), ?unduh=1 untuk disimpan
        $pdf = $this->get("/rekapan/{$nota->id}/cetak?pdf=1");
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('inline', $pdf->headers->get('content-disposition'));
        $unduh = $this->get("/rekapan/{$nota->id}/cetak?pdf=1&unduh=1");
        $this->assertStringStartsWith('attachment', $unduh->headers->get('content-disposition'));
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
        $this->assertSoftDeleted($nota);
        $this->get('/rekapan')->assertDontSee('Rp 230.000');

        // Hapus permanen dari Riwayat Hapus -> detail ikut terhapus
        $this->delete("/riwayat-hapus/nota/{$nota->id}")->assertSessionHas('success');
        $this->assertDatabaseCount('transaksis', 0);
        $this->assertDatabaseCount('transaksi_details', 0);
    }
}
