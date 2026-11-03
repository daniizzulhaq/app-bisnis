@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier')

@php $warna = old('warna', $supplier->warna ?: \App\Models\Supplier::WARNA[0]); @endphp

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" action="{{ $supplier->exists ? route('supplier.update', $supplier) : route('supplier.store') }}" novalidate>
                @csrf
                @if ($supplier->exists) @method('PUT') @endif

                @include('partials.form-kontak', ['model' => $supplier, 'placeholderNama' => 'Contoh: Pak Darto - Pasar Induk'])

                <div class="mt-3">
                    <label class="form-label" for="warna">Warna Penanda <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @foreach (\App\Models\Supplier::WARNA as $w)
                            <button type="button" class="pilih-warna {{ strtolower($warna) === $w ? 'aktif' : '' }}" data-warna="{{ $w }}"
                                    style="--sw: {{ $w }}" title="{{ $w }}" aria-label="Warna {{ $w }}"></button>
                        @endforeach
                        <input type="color" id="warna" name="warna" value="{{ $warna }}"
                               class="form-control form-control-color @error('warna') is-invalid @enderror" title="Warna lain">
                        <span class="ms-1">Contoh: <x-supplier :nama="old('nama', $supplier->nama) ?: 'Nama Supplier'" :warna="$warna" id="contoh-warna" /></span>
                    </div>
                    @error('warna') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <div class="form-text">Warna ini tampil di daftar barang, nota, dan laporan agar tiap supplier mudah dibedakan.</div>
                </div>

                @include('partials.form-aksi', ['batal' => route('supplier.index')])
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const input = document.getElementById('warna');
        const contoh = document.getElementById('contoh-warna');
        const nama = document.getElementById('nama');
        const tombol = document.querySelectorAll('.pilih-warna');

        const terapkan = (w) => {
            input.value = w;
            contoh.style.setProperty('--sw', w);
            tombol.forEach((b) => b.classList.toggle('aktif', b.dataset.warna === w.toLowerCase()));
        };
        tombol.forEach((b) => b.addEventListener('click', () => terapkan(b.dataset.warna)));
        input.addEventListener('input', () => terapkan(input.value));
        nama.addEventListener('input', () => { contoh.lastChild.textContent = nama.value || 'Nama Supplier'; });
    })();
</script>
@endpush
