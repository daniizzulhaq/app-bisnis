@extends('layouts.app')

@section('title', 'Cashflow')
@section('subtitle', 'Arus kas periode '.$periode->label())

@section('content')
    @include('partials.filter-periode', ['kecuali' => ['tipe', 'kategori']])

    <div class="row g-3 mb-3">
        @foreach ([
            ['Saldo Awal', $saldoAwal, 'stat-blue', 'bi-safe'],
            ['Total Masuk', $totalMasuk, 'stat-green', 'bi-arrow-down-left'],
            ['Total Keluar', $totalKeluar, 'stat-red', 'bi-arrow-up-right'],
            ['Saldo Akhir', $saldoAkhir, 'stat-yellow', 'bi-wallet2'],
        ] as [$label, $nilai, $kelas, $ikon])
            <div class="col-6 col-xl-3">
                <div class="card stat-card {{ $kelas }}">
                    <div class="card-body py-3">
                        <div style="min-width:0">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value fs-5 text-truncate {{ $nilai < 0 ? 'text-danger' : '' }}">{{ rupiah($nilai) }}</div>
                        </div>
                        <div class="stat-icon"><i class="bi {{ $ikon }}"></i></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="dari" value="{{ $periode->dariStr() }}">
                <input type="hidden" name="sampai" value="{{ $periode->sampaiStr() }}">
                <select name="tipe" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Semua tipe</option>
                    <option value="masuk" @selected(request('tipe') === 'masuk')>Masuk</option>
                    <option value="keluar" @selected(request('tipe') === 'keluar')>Keluar</option>
                </select>
                <select name="kategori" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoris as $k)
                        <option value="{{ $k }}" @selected(request('kategori') === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('cashflow.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Input Manual</a>
        </div>

        @if ($cashflows->isEmpty())
            <x-empty-state icon="bi-wallet2" title="Belum ada arus kas di periode ini"
                           message="Cashflow terisi otomatis dari Pembayaran, atau tambahkan biaya/modal secara manual." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="num">Masuk</th>
                        <th class="num">Keluar</th>
                        @if ($saldoBerjalan !== null) <th class="num">Saldo</th> @endif
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $saldo = $saldoBerjalan; @endphp
                    @foreach ($cashflows as $c)
                        @php
                            if ($saldo !== null) { $saldo += $c->tipe === 'masuk' ? $c->nominal : -$c->nominal; }
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ tanggal($c->tanggal) }}</td>
                            <td>
                                <span class="fw-semibold">{{ $c->kategori }}</span>
                                @if ($c->isOtomatis())
                                    <span class="badge text-bg-light border ms-1" title="Dibuat otomatis dari Pembayaran"><i class="bi bi-link-45deg"></i> Otomatis</span>
                                @endif
                            </td>
                            <td class="small text-secondary td-wrap">{{ $c->keterangan ?: '-' }}</td>
                            <td class="num text-success">{{ $c->tipe === 'masuk' ? rupiah($c->nominal) : '' }}</td>
                            <td class="num text-danger">{{ $c->tipe === 'keluar' ? rupiah($c->nominal) : '' }}</td>
                            @if ($saldo !== null) <td class="num fw-semibold {{ $saldo < 0 ? 'text-danger' : '' }}">{{ rupiah($saldo) }}</td> @endif
                            <td class="text-end">
                                @if ($c->isOtomatis())
                                    <a href="{{ route('pembayaran.edit', $c->pembayaran_id) }}" class="btn btn-sm btn-light" title="Ubah lewat Pembayaran">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @else
                                    @include('partials.tombol-aksi', [
                                        'edit' => route('cashflow.edit', $c),
                                        'hapus' => route('cashflow.destroy', $c),
                                        'nama' => $c->kategori.' '.rupiah($c->nominal),
                                    ])
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($cashflows->hasPages())
                <div class="card-body border-top">{{ $cashflows->links() }}</div>
            @endif
        @endif
    </div>
@endsection
