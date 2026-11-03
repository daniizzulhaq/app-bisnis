{{-- Halaman laporan (mode HTML). Tabel diambil dari laporan.{jenis}._tabel yang juga dipakai PDF & Excel. --}}
@extends('layouts.app')

@section('title', $judul)
@section('subtitle', 'Periode '.$periode->label().($subjek ? ' · '.$subjek : ''))

@section('content')
    @include('partials.filter-periode')

    <div class="d-flex justify-content-end gap-2 mb-3 filter-actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-light">
            <i class="bi bi-file-earmark-excel text-success me-1"></i> Export Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-light">
            <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
        </a>
    </div>

    @if ($rows->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-bar-chart" title="Tidak ada data pada periode ini"
                           message="Coba pilih periode lain atau tambahkan transaksi terlebih dahulu." />
        </div>
    @else
        @include("laporan.{$jenis}._tabel")
    @endif
@endsection
