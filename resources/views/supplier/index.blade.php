@extends('layouts.app')

@section('title', 'Supplier')
@section('subtitle', 'Daftar petani / pemasok sayur')

@section('content')
    @include('partials.search-bar', [
        'placeholder' => 'Cari nama, no. HP, atau alamat...',
        'tambahUrl' => route('supplier.create'),
        'tambahLabel' => 'Tambah Supplier',
    ])

    <div class="card">
        @if ($suppliers->isEmpty())
            <x-empty-state icon="bi-truck" :title="request('q') ? 'Supplier tidak ditemukan' : 'Belum ada supplier'"
                           :message="request('q') ? 'Coba kata kunci lain.' : 'Tambahkan supplier pertama Anda untuk mulai mencatat pembelian.'">
                @unless (request('q'))
                    <a href="{{ route('supplier.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Supplier</a>
                @endunless
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>Nama</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th class="num">Barang</th>
                        <th class="num">Item Transaksi</th>
                        <th class="text-end" style="width:110px">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr>
                            <td class="text-secondary">{{ $suppliers->firstItem() + $loop->index }}</td>
                            <td>
                                <x-supplier :nama="$supplier->nama" :warna="$supplier->warna" class="fw-semibold" />
                                @if ($supplier->keterangan)
                                    <div class="small text-secondary text-truncate" style="max-width:260px">{{ $supplier->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $supplier->no_hp ?: '-' }}</td>
                            <td class="text-secondary small td-wrap">{{ \Illuminate\Support\Str::limit($supplier->alamat, 60) ?: '-' }}</td>
                            <td class="num">
                                <a href="{{ route('barang.index', ['supplier' => $supplier->id]) }}" class="text-decoration-none" title="Lihat barang supplier ini">{{ angka($supplier->barangs_count) }}</a>
                            </td>
                            <td class="num">{{ angka($supplier->transaksi_details_count) }}</td>
                            <td class="text-end">
                                @include('partials.tombol-aksi', [
                                    'edit' => route('supplier.edit', $supplier),
                                    'hapus' => route('supplier.destroy', $supplier),
                                    'nama' => $supplier->nama,
                                ])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($suppliers->hasPages())
                <div class="card-body border-top">{{ $suppliers->links() }}</div>
            @endif
        @endif
    </div>
@endsection
