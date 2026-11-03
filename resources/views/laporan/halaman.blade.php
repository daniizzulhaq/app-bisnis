{{-- Halaman laporan (mode HTML). Tabel diambil dari laporan.{jenis}._tabel yang juga dipakai PDF & Excel. --}}
@extends('layouts.app')

@section('title', $judul)
@section('subtitle', 'Periode '.$periode->label().($subjek ? ' · '.$subjek : ''))

@section('content')
    @include('laporan._filter')

    <div class="d-flex justify-content-end gap-2 mb-3 filter-actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel', 'unduh' => null]) }}" class="btn btn-light">
            <i class="bi bi-file-earmark-excel text-success me-1"></i> Export Excel
        </a>
        {{-- PDF: pilih lihat dulu di browser atau langsung simpan file --}}
        <div class="btn-group">
            <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item" target="_blank" href="{{ request()->fullUrlWithQuery(['export' => 'pdf', 'unduh' => null]) }}">
                        <i class="bi bi-eye me-2"></i>Lihat PDF <span class="small text-secondary">(tab baru)</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf', 'unduh' => 1]) }}">
                        <i class="bi bi-download me-2"></i>Unduh PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-bar-chart" title="Tidak ada data pada periode ini"
                           message="Coba pilih periode atau filter lain, atau tambahkan transaksi terlebih dahulu." />
        </div>
    @else
        @include("laporan.{$jenis}._tabel")
    @endif
@endsection
