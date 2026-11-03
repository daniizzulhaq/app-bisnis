@extends('layouts.app')

@section('title', 'Barang')
@section('subtitle', 'Daftar sayur & harga default')

@section('content')
    @include('partials.search-bar', [
        'placeholder' => 'Cari nama barang atau satuan...',
        'tambahUrl' => route('barang.create'),
        'tambahLabel' => 'Tambah Barang',
    ])

    <div class="card">
        @if ($barangs->isEmpty())
            <x-empty-state icon="bi-basket2" :title="request('q') ? 'Barang tidak ditemukan' : 'Belum ada barang'"
                           :message="request('q') ? 'Coba kata kunci lain.' : 'Tambahkan sayur yang Anda jual beserta harga default-nya.'">
                @unless (request('q'))
                    <a href="{{ route('barang.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Barang</a>
                @endunless
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
                    @foreach ($barangs as $barang)
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
