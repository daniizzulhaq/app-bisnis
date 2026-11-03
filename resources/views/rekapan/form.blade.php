@extends('layouts.app')

@section('title', $transaksi->exists ? 'Edit Nota '.$transaksi->no_nota : 'Nota Baru')
@section('subtitle', $transaksi->exists ? 'Perubahan total akan menghitung ulang status bayar' : 'Catat penjualan sayur ke klien')

@php
    // Prioritas: input lama (gagal validasi) > detail nota (edit) > 1 baris kosong
    $rows = old('items', $items) ?: [[]];
    $dataBarang = $barangs->mapWithKeys(fn ($b) => [$b->id => [
        'beli' => (float) $b->harga_beli_default,
        'jual' => (float) $b->harga_jual_default,
        'satuan' => $b->satuan,
    ]]);
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
                    <div class="col-md-3">
                        <label class="form-label">No. Nota</label>
                        <input type="text" class="form-control bg-light" value="{{ $transaksi->no_nota ?? 'Otomatis' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $transaksi->tanggal?->toDateString()) }}"
                               class="form-control @error('tanggal') is-invalid @enderror" required>
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Item Barang</span>
                <button type="button" class="btn btn-sm btn-primary" id="btn-tambah-baris">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                </button>
            </div>
            @error('items') <div class="alert alert-danger m-3 mb-0">{{ $message }}</div> @enderror
            <div class="table-responsive">
                <table class="table align-top mb-0 tabel-item">
                    <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th class="num">Qty</th>
                        <th class="num">Harga Beli</th>
                        <th class="num">Harga Jual</th>
                        <th class="num">Subtotal</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="item-body">
                    @foreach ($rows as $i => $item)
                        @include('rekapan._item-row', ['i' => $loop->index, 'item' => $item])
                    @endforeach
                    </tbody>
                    <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="num" id="total-qty">0</td>
                        <td colspan="2"></td>
                        <td class="num">
                            <div>
                                <div id="total-jual">Rp 0</div>
                                <div class="small text-secondary fw-normal">beli <span id="total-beli">Rp 0</span></div>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3" class="form-control @error('keterangan') is-invalid @enderror"
                                  placeholder="Catatan nota (opsional), mis. dikirim jam 05.00">{{ old('keterangan', $transaksi->keterangan) }}</textarea>
                        @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Total Beli</span><span class="fw-semibold" id="ring-beli">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Total Jual</span><span class="fw-semibold" id="ring-jual">Rp 0</span></div>
                        <div class="d-flex justify-content-between pt-2 border-top"><span class="fw-bold">Laba</span><span class="fw-bold fs-5" id="ring-laba">Rp 0</span></div>
                        <div class="small text-secondary mt-2"><i class="bi bi-info-circle me-1"></i>Angka final dihitung ulang oleh server saat disimpan.</div>
                    </div>
                </div>
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

    /** Hitung subtotal per baris & total nota (hanya tampilan; server menghitung ulang). */
    function hitung() {
        let tQty = 0, tBeli = 0, tJual = 0;
        body.querySelectorAll('.item-row').forEach((row, idx) => {
            const qty = RB.parseQty(row.querySelector('.inp-qty').value);
            const beli = Math.round(qty * RB.parseUang(row.querySelector('.inp-beli').value));
            const jual = Math.round(qty * RB.parseUang(row.querySelector('.inp-jual').value));
            row.querySelector('.row-no').textContent = idx + 1;
            row.querySelector('.sub-beli').textContent = RB.rupiah(beli);
            row.querySelector('.sub-jual').textContent = RB.rupiah(jual);
            tQty += qty; tBeli += beli; tJual += jual;
        });
        const laba = tJual - tBeli;
        document.getElementById('total-qty').textContent = RB.angka(tQty);
        document.getElementById('total-beli').textContent = RB.rupiah(tBeli);
        document.getElementById('total-jual').textContent = RB.rupiah(tJual);
        document.getElementById('ring-beli').textContent = RB.rupiah(tBeli);
        document.getElementById('ring-jual').textContent = RB.rupiah(tJual);
        const elLaba = document.getElementById('ring-laba');
        elLaba.textContent = RB.rupiah(laba);
        elLaba.className = 'fw-bold fs-5 ' + (laba < 0 ? 'text-danger' : 'text-success');
    }

    /** Pasang perilaku pada satu baris: harga default saat barang dipilih, label satuan. */
    function siapkanBaris(row) {
        const selBarang = row.querySelector('.sel-barang');
        const tampilSatuan = () => {
            const b = BARANG[selBarang.value];
            row.querySelector('.lbl-satuan').textContent = b ? b.satuan : '';
        };
        selBarang.addEventListener('change', () => {
            const b = BARANG[selBarang.value];
            if (b) {
                // Harga otomatis dari default barang, tetap bisa diubah manual
                row.querySelector('.inp-beli').value = Math.round(b.beli).toLocaleString('id-ID');
                row.querySelector('.inp-jual').value = Math.round(b.jual).toLocaleString('id-ID');
            }
            tampilSatuan();
            hitung();
            row.querySelector('.inp-qty').focus();
        });
        tampilSatuan();
    }

    function tambahBaris() {
        const html = tpl.innerHTML.replaceAll('__INDEX__', indexBerikut++);
        body.insertAdjacentHTML('beforeend', html);
        const row = body.lastElementChild;

        // Supplier baris baru mengikuti baris sebelumnya (biasanya 1 supplier untuk banyak item)
        const sebelumnya = row.previousElementSibling?.querySelector('.sel-supplier')?.value;
        if (sebelumnya) row.querySelector('.sel-supplier').value = sebelumnya;

        row.querySelectorAll('select').forEach((s) => s.classList.add('tom-select'));
        RB.initTomSelect(row);
        RB.labelTabel();
        siapkanBaris(row);
        hitung();
        row.querySelector('.sel-barang').tomselect?.focus();
    }

    document.getElementById('btn-tambah-baris').addEventListener('click', tambahBaris);

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
