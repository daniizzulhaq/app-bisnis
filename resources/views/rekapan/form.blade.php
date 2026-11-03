@extends('layouts.app')

@section('title', $transaksi->exists ? 'Edit Nota' : 'Nota Baru')
@section('subtitle', $transaksi->exists ? $transaksi->klien->nama.' · '.tanggal($transaksi->tanggal).' · perubahan total akan menghitung ulang status bayar' : 'Catat penjualan sayur ke klien')

@php
    // Prioritas: input lama (gagal validasi) > detail nota (edit) > 10 baris kosong (nota baru)
    $rows = old('items', $items) ?: array_fill(0, 10, []);
    $dataBarang = $barangs->mapWithKeys(fn ($b) => [$b->id => [
        'beli' => (float) $b->harga_beli_default,
        'jual' => (float) $b->harga_jual_default,
        'satuan' => $b->satuan,
        'supplier' => $b->supplier?->id, // supplier di Riwayat Hapus tidak diisi otomatis
    ]]);

    // Pilihan barang dikelompokkan per supplier (tanpa supplier di akhir)
    $grupBarang = $barangs->groupBy(fn ($b) => $b->supplier?->id ?? 0)
        ->map(fn ($list) => [
            'nama' => $list->first()->supplier?->nama ?? 'Tanpa supplier',
            'warna' => $list->first()->supplier?->warna ?? '#94a3b8',
            'barangs' => $list,
        ])
        ->sortBy(fn ($g) => ($g['nama'] === 'Tanpa supplier' ? '1' : '0').$g['nama'])
        ->values();
@endphp

@section('content')
    @if ($barangs->isEmpty() || $suppliers->isEmpty() || $kliens->isEmpty())
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                Lengkapi data master terlebih dahulu:
                @if ($kliens->isEmpty()) <a href="{{ route('klien.create') }}">tambah klien</a>@endif
                @if ($barangs->isEmpty()) <a href="{{ route('barang.create') }}">tambah barang</a>@endif
                @if ($suppliers->isEmpty()) <a href="{{ route('supplier.create') }}">tambah supplier</a>@endif
            </div>
        </div>
    @endif

    <form method="POST" id="form-nota" novalidate
          action="{{ $transaksi->exists ? route('rekapan.update', $transaksi) : route('rekapan.store') }}">
        @csrf
        @if ($transaksi->exists) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $transaksi->tanggal?->toDateString()) }}"
                               class="form-control @error('tanggal') is-invalid @enderror" required>
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label for="klien_id" class="form-label">Klien <span class="text-danger">*</span></label>
                        <select id="klien_id" name="klien_id" class="form-select tom-select @error('klien_id') is-invalid @enderror"
                                data-placeholder="Pilih hotel / resto...">
                            <option value="">Pilih hotel / resto...</option>
                            @foreach ($kliens as $k)
                                <option value="{{ $k->id }}" @selected((string) old('klien_id', $transaksi->klien_id) === (string) $k->id)>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                        @error('klien_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Item Barang <span class="small text-secondary fw-normal">· supplier terisi otomatis dari barang, baris kosong diabaikan</span>
            </div>
            @error('items') <div class="alert alert-danger m-3 mb-0">{{ $message }}</div> @enderror
            <div class="table-responsive">
                <table class="table align-top mb-0 tabel-item">
                    <thead>
                    <tr>
                        <th class="col-no">#</th>
                        <th class="col-supplier">Supplier</th>
                        <th class="col-barang">Barang</th>
                        <th class="col-qty text-center">Qty</th>
                        <th class="col-harga text-center">Harga Jual</th>
                        <th class="col-harga text-center">Harga Beli</th>
                        <th class="col-satuan text-center">Satuan</th>
                        <th class="col-total text-center">Total Jual</th>
                        <th class="col-total text-center">Total Beli</th>
                        <th class="col-aksi"></th>
                    </tr>
                    </thead>
                    <tbody id="item-body">
                    @foreach ($rows as $i => $item)
                        @include('rekapan._item-row', ['i' => $loop->index, 'item' => $item])
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                {{-- Tambah beberapa baris sekaligus --}}
                <div class="input-group tambah-baris mb-3">
                    <input type="number" id="jumlah-baris" class="form-control" value="1" min="1" max="50"
                           inputmode="numeric" aria-label="Jumlah baris yang ditambah" title="Jumlah baris yang ditambah">
                    <button type="button" class="btn btn-primary" id="btn-tambah-baris">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Item
                    </button>
                </div>

                <div class="total-nota">
                    <div><label for="total-jual">Total Jual:</label> <input type="text" id="total-jual" class="form-control text-end fw-bold" readonly tabindex="-1"></div>
                    <div><label for="total-beli">Total Beli:</label> <input type="text" id="total-beli" class="form-control text-end fw-bold" readonly tabindex="-1"></div>
                    <div class="text-secondary small">Laba: <span id="total-laba" class="fw-bold">Rp 0</span> · Total qty: <span id="total-qty">0</span> · angka final dihitung ulang server saat disimpan</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror"
                          placeholder="Catatan nota (opsional), mis. dikirim jam 05.00">{{ old('keterangan', $transaksi->keterangan) }}</textarea>
                @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3 form-actions">
            <a href="{{ $transaksi->exists ? route('rekapan.show', $transaksi) : route('rekapan.index') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary" data-loading="Menyimpan...">
                <i class="bi bi-check2 me-1"></i> Simpan Nota
            </button>
        </div>
    </form>

    {{-- Template baris baru untuk JavaScript --}}
    <template id="tpl-baris">
        @include('rekapan._item-row', ['i' => '__INDEX__', 'item' => []])
    </template>
@endsection

@push('scripts')
<script>
(() => {
    const BARANG = @json($dataBarang);
    const body = document.getElementById('item-body');
    const tpl = document.getElementById('tpl-baris');
    let indexBerikut = body.querySelectorAll('.item-row').length;

    // Total per baris & total nota (hanya tampilan; server menghitung ulang). 0 ditampilkan kosong.
    const angkaRp = (n) => (n ? Math.round(n).toLocaleString('id-ID') : '');

    function hitung() {
        let tQty = 0, tBeli = 0, tJual = 0;
        body.querySelectorAll('.item-row').forEach((row, idx) => {
            const qty = RB.parseQty(row.querySelector('.inp-qty').value);
            const beli = Math.round(qty * RB.parseUang(row.querySelector('.inp-beli').value));
            const jual = Math.round(qty * RB.parseUang(row.querySelector('.inp-jual').value));
            row.querySelector('.row-no').textContent = idx + 1;
            row.querySelector('.inp-total-jual').value = angkaRp(jual);
            row.querySelector('.inp-total-beli').value = angkaRp(beli);
            tQty += qty; tBeli += beli; tJual += jual;
        });
        const laba = tJual - tBeli;
        document.getElementById('total-qty').textContent = RB.angka(tQty);
        document.getElementById('total-jual').value = RB.rupiah(tJual);
        document.getElementById('total-beli').value = RB.rupiah(tBeli);
        const elLaba = document.getElementById('total-laba');
        elLaba.textContent = RB.rupiah(laba);
        elLaba.className = 'fw-bold ' + (laba < 0 ? 'text-danger' : 'text-success');
    }

    /** Pasang perilaku satu baris: saat barang dipilih -> supplier, harga & satuan terisi otomatis. */
    function siapkanBaris(row) {
        const selBarang = row.querySelector('.sel-barang');
        const tampilSatuan = () => {
            const b = BARANG[selBarang.value];
            row.querySelector('.inp-satuan').value = b ? b.satuan : '';
        };
        selBarang.addEventListener('change', () => {
            const b = BARANG[selBarang.value];
            if (b) {
                // Harga otomatis dari default barang, tetap bisa diubah manual
                row.querySelector('.inp-beli').value = Math.round(b.beli).toLocaleString('id-ID');
                row.querySelector('.inp-jual').value = Math.round(b.jual).toLocaleString('id-ID');
                // Supplier otomatis mengikuti supplier barang (tetap bisa diganti)
                if (b.supplier) {
                    const selSup = row.querySelector('.sel-supplier');
                    selSup.tomselect ? selSup.tomselect.setValue(String(b.supplier), true) : (selSup.value = b.supplier);
                }
            }
            tampilSatuan();
            hitung();
            row.querySelector('.inp-qty').focus();
        });
        tampilSatuan();
    }

    /** Tambah n baris kosong sekaligus, lalu fokus ke barang di baris pertama yang baru. */
    function tambahBaris(n = 1) {
        let pertama = null;
        for (let k = 0; k < n; k++) {
            body.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__INDEX__', indexBerikut++));
            const row = body.lastElementChild;
            row.querySelectorAll('select').forEach((s) => s.classList.add('tom-select'));
            RB.initTomSelect(row);
            siapkanBaris(row);
            pertama ??= row;
        }
        RB.labelTabel();
        hitung();
        pertama.querySelector('.sel-barang').tomselect?.focus();
    }

    const inpJumlah = document.getElementById('jumlah-baris');
    const tambahSesuaiJumlah = () => {
        const n = Math.min(50, Math.max(1, parseInt(inpJumlah.value, 10) || 1));
        inpJumlah.value = n;
        if (body.querySelectorAll('.item-row').length + n > 200) {
            RB.toast.fire({ icon: 'warning', title: 'Maksimal 200 item per nota.' });
            return;
        }
        tambahBaris(n);
    };
    document.getElementById('btn-tambah-baris').addEventListener('click', tambahSesuaiJumlah);
    // Enter di kotak jumlah = tambah baris (bukan submit form)
    inpJumlah.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); tambahSesuaiJumlah(); }
    });

    body.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-hapus-baris');
        if (!btn) return;
        if (body.querySelectorAll('.item-row').length === 1) {
            RB.toast.fire({ icon: 'warning', title: 'Nota minimal berisi 1 item.' });
            return;
        }
        btn.closest('.item-row').remove();
        hitung();
    });

    body.addEventListener('input', (e) => {
        if (e.target.matches('.inp-qty, .inp-beli, .inp-jual')) hitung();
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Baris yang sudah ada (edit / gagal validasi). Harga lama tidak ditimpa
        // kecuali barangnya diganti oleh user.
        body.querySelectorAll('.item-row').forEach(siapkanBaris);
        hitung();
    });
})();
</script>
@endpush
