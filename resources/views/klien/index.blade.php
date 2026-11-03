@extends('layouts.app')

@section('title', 'Klien')
@section('subtitle', 'Daftar hotel & restoran pelanggan')

@section('content')
    @include('partials.search-bar', [
        'placeholder' => 'Cari nama, no. HP, atau alamat...',
        'tambahUrl' => route('klien.create'),
        'tambahLabel' => 'Tambah Klien',
    ])

    <div class="card">
        @if ($kliens->isEmpty())
            <x-empty-state icon="bi-building" :title="request('q') ? 'Klien tidak ditemukan' : 'Belum ada klien'"
                           :message="request('q') ? 'Coba kata kunci lain.' : 'Tambahkan hotel atau resto pelanggan Anda.'">
                @unless (request('q'))
                    <a href="{{ route('klien.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Klien</a>
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
                        <th class="num">Jumlah Nota</th>
                        <th class="num">Total Belanja</th>
                        <th class="text-end" style="width:110px">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($kliens as $klien)
                        <tr>
                            <td class="text-secondary">{{ $kliens->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $klien->nama }}</div>
                                @if ($klien->keterangan)
                                    <div class="small text-secondary text-truncate" style="max-width:260px">{{ $klien->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $klien->no_hp ?: '-' }}</td>
                            <td class="text-secondary small td-wrap">{{ \Illuminate\Support\Str::limit($klien->alamat, 60) ?: '-' }}</td>
                            <td class="num">{{ angka($klien->transaksis_count) }}</td>
                            <td class="num fw-semibold">{{ rupiah($klien->transaksis_sum_total_jual) }}</td>
                            <td class="text-end">
                                @include('partials.tombol-aksi', [
                                    'edit' => route('klien.edit', $klien),
                                    'hapus' => route('klien.destroy', $klien),
                                    'nama' => $klien->nama,
                                ])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($kliens->hasPages())
                <div class="card-body border-top">{{ $kliens->links() }}</div>
            @endif
        @endif
    </div>
@endsection
