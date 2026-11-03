@extends('layouts.app')

@section('title', 'Barang')
@section('subtitle', 'Daftar sayur per supplier & harga default')

@php
    // Link klasifikasi tetap membawa kata kunci pencarian
    $urlGrup = fn ($nilai) => route('barang.index', array_filter(['q' => request('q'), 'supplier' => $nilai]));
    $tambahUrl = route('barang.create', is_numeric($pilih) ? ['supplier' => $pilih] : []);
@endphp

@section('content')
    @include('partials.search-bar', [
        'placeholder' => 'Cari nama barang atau satuan...',
        'tambahUrl' => $tambahUrl,
        'tambahLabel' => 'Tambah Barang',
        'tersembunyi' => ['supplier' => $pilih],
    ])

    {{-- Klasifikasi barang per supplier --}}
    <div class="grup-supplier d-flex flex-wrap gap-2 mb-3">
        <a href="{{ $urlGrup(null) }}" class="btn btn-sm {{ $pilih === null || $pilih === '' ? 'btn-primary' : 'btn-light' }}">
            Semua <span class="badge rounded-pill bg-white text-dark ms-1">{{ $jumlahSemua }}</span>
        </a>
        @foreach ($suppliers as $s)
            <a href="{{ $urlGrup($s->id) }}" class="btn btn-sm btn-light chip-supplier {{ (string) $pilih === (string) $s->id ? 'aktif' : '' }}" style="--sw: {{ $s->warna }}">
                <i></i>{{ $s->nama }} <span class="badge rounded-pill ms-1">{{ $s->jumlah_barang }}</span>
            </a>
        @endforeach
        @if ($jumlahTanpa)
            <a href="{{ $urlGrup('tanpa') }}" class="btn btn-sm btn-light chip-supplier {{ $pilih === 'tanpa' ? 'aktif' : '' }}" style="--sw: #94a3b8">
                <i></i>Tanpa supplier <span class="badge rounded-pill ms-1">{{ $jumlahTanpa }}</span>
            </a>
        @endif
    </div>

    <div class="card">
        @if ($barangs->isEmpty())
            <x-empty-state icon="bi-basket2" :title="request('q') || $pilih ? 'Barang tidak ditemukan' : 'Belum ada barang'"
                           :message="request('q') || $pilih ? 'Coba kata kunci atau supplier lain.' : 'Tambahkan sayur yang Anda jual beserta supplier dan harga default-nya.'">
                <a href="{{ $tambahUrl }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Barang</a>
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th class="num">Harga Beli</th>
                        <th class="num">Harga Jual</th>
                        <th class="num">Margin</th>
                        <th class="num">Dipakai</th>
                        <th class="text-end" style="width:110px">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $grupSebelumnya = false; @endphp
                    @foreach ($barangs as $barang)
                        {{-- Judul kelompok setiap ganti supplier --}}
                        @if (($barang->supplier?->id) !== $grupSebelumnya)
                            @php $grupSebelumnya = $barang->supplier?->id; @endphp
                            <tr class="baris-grup">
                                <td colspan="8" data-label="">
                                    <x-supplier :nama="$barang->supplier?->nama ?? 'Tanpa supplier'" :warna="$barang->supplier?->warna" class="fw-semibold" />
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-secondary">{{ $barangs->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $barang->nama }}</td>
                            <td><span class="badge text-bg-light border">{{ $barang->satuan }}</span></td>
                            <td class="num">{{ rupiah($barang->harga_beli_default) }}</td>
                            <td class="num">{{ rupiah($barang->harga_jual_default) }}</td>
                            <td class="num {{ $barang->margin_default < 0 ? 'text-danger' : 'text-success' }}">
                                {{ rupiah($barang->margin_default) }}
                            </td>
                            <td class="num text-secondary">{{ angka($barang->transaksi_details_count) }}x</td>
                            <td class="text-end">
                                @include('partials.tombol-aksi', [
                                    'edit' => route('barang.edit', $barang),
                                    'hapus' => route('barang.destroy', $barang),
                                    'nama' => $barang->nama,
                                ])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($barangs->hasPages())
                <div class="card-body border-top">{{ $barangs->links() }}</div>
            @endif
        @endif
    </div>
@endsection
