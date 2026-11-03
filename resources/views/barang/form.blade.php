@extends('layouts.app')

@section('title', $barang->exists ? 'Edit Barang' : 'Tambah Barang')

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" action="{{ $barang->exists ? route('barang.update', $barang) : route('barang.store') }}" novalidate>
                @csrf
                @if ($barang->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select id="supplier_id" name="supplier_id" class="form-select tom-select tom-warna @error('supplier_id') is-invalid @enderror"
                                data-placeholder="Pilih supplier langganan...">
                            <option value="">Tanpa supplier</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" data-warna="{{ $s->warna }}"
                                    @selected((string) old('supplier_id', $barang->supplier_id) === (string) $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">
                            Barang dikelompokkan per supplier, dan supplier ini otomatis terpilih saat barang dipakai di nota.
                            @if ($suppliers->isEmpty()) <a href="{{ route('supplier.create') }}">Tambah supplier</a> dulu. @endif
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label for="nama" class="form-label">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" id="nama" name="nama" value="{{ old('nama', $barang->nama) }}" maxlength="100"
                               class="form-control @error('nama') is-invalid @enderror" placeholder="Contoh: Wortel Brastagi" required autofocus>
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="satuan" class="form-label">Satuan <span class="text-danger">*</span></label>
                        <input type="text" id="satuan" name="satuan" value="{{ old('satuan', $barang->satuan) }}" list="daftar-satuan"
                               class="form-control @error('satuan') is-invalid @enderror" placeholder="kg / ikat / pcs" required maxlength="20">
                        <datalist id="daftar-satuan">
                            @foreach (\App\Models\Barang::SATUAN as $s)
                                <option value="{{ $s }}">
                            @endforeach
                        </datalist>
                        @error('satuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="harga_beli_default" class="form-label">Harga Beli Default <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="harga_beli_default" name="harga_beli_default" inputmode="numeric"
                                   value="{{ input_uang(old('harga_beli_default', $barang->harga_beli_default)) }}"
                                   class="form-control input-uang text-end @error('harga_beli_default') is-invalid @enderror" placeholder="0" required>
                            @error('harga_beli_default') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Harga dari supplier per satuan.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="harga_jual_default" class="form-label">Harga Jual Default <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="harga_jual_default" name="harga_jual_default" inputmode="numeric"
                                   value="{{ input_uang(old('harga_jual_default', $barang->harga_jual_default)) }}"
                                   class="form-control input-uang text-end @error('harga_jual_default') is-invalid @enderror" placeholder="0" required>
                            @error('harga_jual_default') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Harga ke hotel/resto per satuan. <span id="info-margin"></span></div>
                    </div>
                </div>

                @include('partials.form-aksi', ['batal' => route('barang.index')])
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Tampilkan margin live
    (() => {
        const beli = document.getElementById('harga_beli_default');
        const jual = document.getElementById('harga_jual_default');
        const info = document.getElementById('info-margin');
        const hitung = () => {
            const m = RB.parseUang(jual.value) - RB.parseUang(beli.value);
            info.innerHTML = jual.value ? `Margin: <b class="${m < 0 ? 'text-danger' : 'text-success'}">${RB.rupiah(m)}</b>` : '';
        };
        [beli, jual].forEach((el) => el.addEventListener('input', hitung));
        document.addEventListener('DOMContentLoaded', hitung);
    })();
</script>
@endpush
