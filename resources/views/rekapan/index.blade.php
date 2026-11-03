@extends('layouts.app')

@section('title', 'Rekapan')
@section('subtitle', 'Daftar nota penjualan ke hotel & resto')

@section('content')
    {{-- HP: filter dilipat agar daftar langsung terlihat --}}
    @php $jumlahFilter = count(array_filter(request()->only(['dari','sampai','klien_id','supplier_id','barang_id','status','q']))); @endphp
    <div class="d-flex d-md-none gap-2 mb-3">
        <button class="btn btn-light flex-fill" type="button" data-bs-toggle="collapse" data-bs-target="#filterRekapan" aria-expanded="false" aria-controls="filterRekapan">
            <i class="bi bi-funnel me-1"></i> Filter
            @if ($jumlahFilter) <span class="badge rounded-pill text-bg-primary ms-1">{{ $jumlahFilter }}</span> @endif
        </button>
        <a href="{{ route('rekapan.create') }}" class="btn btn-primary flex-fill"><i class="bi bi-plus-lg me-1"></i> Nota Baru</a>
    </div>

    {{-- Filter --}}
    <div class="card mb-3 collapse d-md-block" id="filterRekapan">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="dari">Dari</label>
                    <input type="date" id="dari" name="dari" value="{{ $filter['dari'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="sampai">Sampai</label>
                    <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-4 col-xl-2">
                    <label class="form-label" for="f-klien">Klien</label>
                    <select id="f-klien" name="klien_id" class="form-select tom-select" data-placeholder="Semua klien">
                        <option value="">Semua klien</option>
                        @foreach ($kliens as $k)
                            <option value="{{ $k->id }}" @selected(($filter['klien_id'] ?? '') == $k->id)>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-xl-2">
                    <label class="form-label" for="f-supplier">Supplier</label>
                    <select id="f-supplier" name="supplier_id" class="form-select tom-select" data-placeholder="Semua supplier">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(($filter['supplier_id'] ?? '') == $s->id)>{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-xl-2">
                    <label class="form-label" for="f-barang">Barang</label>
                    <select id="f-barang" name="barang_id" class="form-select tom-select" data-placeholder="Semua barang">
                        <option value="">Semua barang</option>
                        @foreach ($barangs as $b)
                            <option value="{{ $b->id }}" @selected(($filter['barang_id'] ?? '') == $b->id)>{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-xl-2">
                    <label class="form-label" for="f-status">Status</label>
                    <select id="f-status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Transaksi::STATUS as $val => $label)
                            <option value="{{ $val }}" @selected(($filter['status'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="q">No. Nota</label>
                    <input type="search" id="q" name="q" value="{{ $filter['q'] ?? '' }}" class="form-control" placeholder="NT-2611-...">
                </div>
                <div class="col-md-8 d-flex gap-2 justify-content-md-end filter-actions">
                    <a href="{{ route('rekapan.index') }}" class="btn btn-light">Reset</a>
                    <button class="btn btn-light"><i class="bi bi-funnel me-1"></i> Terapkan Filter</button>
                    <a href="{{ route('rekapan.create') }}" class="btn btn-primary d-none d-md-inline-block"><i class="bi bi-plus-lg me-1"></i> Nota Baru</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ringkasan hasil filter --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Jumlah Nota', angka($ringkasan->nota), 'stat-blue', 'bi-receipt'],
            ['Total Beli', rupiah($ringkasan->beli), 'stat-red', 'bi-cart3'],
            ['Total Jual', rupiah($ringkasan->jual), 'stat-yellow', 'bi-graph-up-arrow'],
            ['Laba', rupiah($ringkasan->laba), 'stat-green', 'bi-piggy-bank'],
        ] as [$label, $nilai, $kelas, $ikon])
            <div class="col-6 col-xl-3">
                <div class="card stat-card {{ $kelas }}">
                    <div class="card-body py-3">
                        <div style="min-width:0">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value fs-5 text-truncate">{{ $nilai }}</div>
                        </div>
                        <div class="stat-icon"><i class="bi {{ $ikon }}"></i></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        @if ($transaksis->isEmpty())
            <x-empty-state icon="bi-receipt-cutoff"
                           :title="array_filter($filter) ? 'Tidak ada nota yang cocok' : 'Belum ada nota'"
                           :message="array_filter($filter) ? 'Ubah atau reset filter untuk melihat nota lain.' : 'Catat penjualan pertama Anda ke hotel / resto.'">
                <a href="{{ route('rekapan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nota Baru</a>
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>No. Nota</th>
                        <th>Tanggal</th>
                        <th>Klien</th>
                        <th class="num col-opsional">Item</th>
                        <th class="num col-opsional">Total Qty</th>
                        <th class="num col-opsional-2">Total Beli</th>
                        <th class="num">Total Jual</th>
                        <th class="num">Laba</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($transaksis as $t)
                        <tr>
                            <td><a href="{{ route('rekapan.show', $t) }}" class="fw-semibold text-decoration-none">{{ $t->no_nota }}</a></td>
                            <td class="text-nowrap">{{ tanggal($t->tanggal) }}</td>
                            <td>{{ $t->klien->nama }}</td>
                            <td class="num text-secondary hide-mobile col-opsional">{{ $t->details_count }}</td>
                            <td class="num hide-mobile col-opsional">{{ angka($t->total_qty) }}</td>
                            <td class="num text-secondary hide-mobile col-opsional-2">{{ rupiah($t->total_beli) }}</td>
                            <td class="num fw-semibold">{{ rupiah($t->total_jual) }}</td>
                            <td class="num {{ $t->laba < 0 ? 'text-danger' : 'text-success' }}">{{ rupiah($t->laba) }}</td>
                            <td><x-status-bayar :status="$t->status_bayar" /></td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('rekapan.show', $t) }}" class="btn btn-sm btn-light" title="Detail"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('rekapan.edit', $t) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="{{ route('rekapan.cetak', $t) }}" target="_blank" class="btn btn-sm btn-light" title="Cetak nota"><i class="bi bi-printer"></i></a>
                                    <form method="POST" action="{{ route('rekapan.destroy', $t) }}" class="form-delete" data-nama="nota {{ $t->no_nota }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger" title="Hapus"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($transaksis->hasPages())
                <div class="card-body border-top">{{ $transaksis->links() }}</div>
            @endif
        @endif
    </div>
@endsection
