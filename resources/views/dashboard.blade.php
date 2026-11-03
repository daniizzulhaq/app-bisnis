@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan bisnis periode '.$periode->label())

@section('content')
    @include('partials.filter-periode')

    @php
        $stats = [
            ['label' => 'Transaksi', 'value' => angka($stat->nota).' nota', 'icon' => 'bi-receipt', 'class' => 'stat-blue'],
            ['label' => 'Total Qty', 'value' => angka($stat->qty), 'icon' => 'bi-box-seam', 'class' => 'stat-green'],
            ['label' => 'Total Jual', 'value' => rupiah($stat->jual), 'icon' => 'bi-graph-up-arrow', 'class' => 'stat-yellow'],
            ['label' => 'Total Beli', 'value' => rupiah($stat->beli), 'icon' => 'bi-cart3', 'class' => 'stat-red'],
        ];
        $tops = [
            ['title' => 'Top 3 Supplier', 'icon' => 'bi-truck', 'items' => $topSupplier,
                'utama' => fn ($r) => rupiah($r->total), 'sub' => fn ($r) => $r->nota.' nota · total beli'],
            ['title' => 'Top 3 Barang', 'icon' => 'bi-basket2', 'items' => $topBarang,
                'utama' => fn ($r) => angka($r->qty).' '.$r->satuan, 'sub' => fn ($r) => 'jual '.rupiah($r->total)],
            ['title' => 'Top 3 Klien', 'icon' => 'bi-building', 'items' => $topKlien,
                'utama' => fn ($r) => rupiah($r->total), 'sub' => fn ($r) => $r->nota.' nota · total jual'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($stats as $s)
            <div class="col-6 col-xl-3">
                <div class="card stat-card {{ $s['class'] }}">
                    <div class="card-body">
                        <div style="min-width:0">
                            <div class="stat-label">{{ $s['label'] }}</div>
                            <div class="stat-value text-truncate">{{ $s['value'] }}</div>
                        </div>
                        <div class="stat-icon"><i class="bi {{ $s['icon'] }}"></i></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        @foreach ($tops as $top)
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">{{ $top['title'] }}</div>
                    <div class="card-body py-2">
                        @if ($top['items']->isEmpty())
                            <x-empty-state :icon="$top['icon']" title="Belum ada data" message="Data muncul setelah ada transaksi di periode ini." class="py-3" />
                        @else
                            <ul class="rank-list">
                                @foreach ($top['items'] as $r)
                                    <li class="rank-item">
                                        <span class="rank-badge">{{ $loop->iteration }}</span>
                                        <div class="flex-grow-1" style="min-width:0">
                                            <div class="fw-semibold text-truncate">{{ $r->nama }}</div>
                                            <div class="small text-secondary">{{ $top['sub']($r) }}</div>
                                        </div>
                                        <div class="num fw-bold">{{ $top['utama']($r) }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>5 Transaksi Terakhir</span>
            <a href="{{ route('rekapan.index') }}" class="btn btn-sm btn-light">Lihat semua</a>
        </div>
        @if ($terakhir->isEmpty())
            <x-empty-state icon="bi-receipt-cutoff" title="Belum ada transaksi" message="Mulai catat nota penjualan pertama Anda.">
                <a href="{{ route('rekapan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nota Baru</a>
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Klien</th>
                        <th>No. Nota</th>
                        <th class="num">Total Jual</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($terakhir as $t)
                        <tr>
                            <td class="text-nowrap">{{ tanggal($t->tanggal) }}</td>
                            <td class="fw-semibold">{{ $t->klien->nama }}</td>
                            <td><a href="{{ route('rekapan.show', $t) }}" class="text-decoration-none">{{ $t->no_nota }}</a></td>
                            <td class="num fw-semibold">{{ rupiah($t->total_jual) }}</td>
                            <td><x-status-bayar :status="$t->status_bayar" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
