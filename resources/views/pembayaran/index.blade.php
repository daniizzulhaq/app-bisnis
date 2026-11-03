@extends('layouts.app')

@section('title', 'Pembayaran')
@section('subtitle', 'Pelunasan piutang klien & hutang supplier')

@section('content')
    {{-- HP: filter dilipat agar daftar langsung terlihat --}}
    @php $jumlahFilter = count(array_filter(request()->only(['dari','sampai','jenis','klien_id','supplier_id','metode']))); @endphp
    <div class="d-flex d-md-none gap-2 mb-3">
        <button class="btn btn-light flex-fill" type="button" data-bs-toggle="collapse" data-bs-target="#filterPembayaran" aria-expanded="false" aria-controls="filterPembayaran">
            <i class="bi bi-funnel me-1"></i> Filter
            @if ($jumlahFilter) <span class="badge rounded-pill text-bg-primary ms-1">{{ $jumlahFilter }}</span> @endif
        </button>
        <a href="{{ route('pembayaran.create') }}" class="btn btn-primary flex-fill"><i class="bi bi-plus-lg me-1"></i> Catat Pembayaran</a>
    </div>

    <div class="card mb-3 collapse d-md-block" id="filterPembayaran">
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
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="jenis">Jenis</label>
                    <select id="jenis" name="jenis" class="form-select">
                        <option value="">Semua</option>
                        <option value="klien" @selected(($filter['jenis'] ?? '') === 'klien')>Dari Klien</option>
                        <option value="supplier" @selected(($filter['jenis'] ?? '') === 'supplier')>Ke Supplier</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="metode">Metode</label>
                    <select id="metode" name="metode" class="form-select">
                        <option value="">Semua</option>
                        @foreach (\App\Models\Pembayaran::METODE as $val => $label)
                            <option value="{{ $val }}" @selected(($filter['metode'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                <div class="col-12 d-flex gap-2 justify-content-end filter-actions">
                    <a href="{{ route('pembayaran.index') }}" class="btn btn-light">Reset</a>
                    <button class="btn btn-light"><i class="bi bi-funnel me-1"></i> Terapkan Filter</button>
                    <a href="{{ route('pembayaran.create') }}" class="btn btn-primary d-none d-md-inline-block"><i class="bi bi-plus-lg me-1"></i> Catat Pembayaran</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <div class="card stat-card stat-green"><div class="card-body py-3">
                <div><div class="stat-label">Diterima dari Klien</div><div class="stat-value fs-5">{{ rupiah($ringkasan->masuk) }}</div></div>
                <div class="stat-icon"><i class="bi bi-arrow-down-left"></i></div>
            </div></div>
        </div>
        <div class="col-6">
            <div class="card stat-card stat-red"><div class="card-body py-3">
                <div><div class="stat-label">Dibayar ke Supplier</div><div class="stat-value fs-5">{{ rupiah($ringkasan->keluar) }}</div></div>
                <div class="stat-icon"><i class="bi bi-arrow-up-right"></i></div>
            </div></div>
        </div>
    </div>

    <div class="card">
        @if ($pembayarans->isEmpty())
            <x-empty-state icon="bi-credit-card-2-front" title="Belum ada pembayaran"
                           message="Catat pembayaran dari klien atau ke supplier. Cashflow akan terisi otomatis.">
                <a href="{{ route('pembayaran.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Catat Pembayaran</a>
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Pihak</th>
                        <th>Nota</th>
                        <th>Metode</th>
                        <th class="num">Jumlah</th>
                        <th class="col-opsional-2">Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pembayarans as $p)
                        <tr>
                            <td class="text-nowrap">{{ tanggal($p->tanggal) }}</td>
                            <td>
                                @if ($p->jenis === 'klien')
                                    <span class="badge badge-lunas"><i class="bi bi-arrow-down-left me-1"></i>Masuk</span>
                                @else
                                    <span class="badge badge-belum"><i class="bi bi-arrow-up-right me-1"></i>Keluar</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $p->nama_pihak }}</td>
                            <td>
                                @if ($p->transaksi)
                                    <a href="{{ route('rekapan.show', $p->transaksi_id) }}" class="text-decoration-none">Nota {{ tanggal($p->transaksi->tanggal) }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ \App\Models\Pembayaran::METODE[$p->metode] }}</td>
                            <td class="num fw-semibold {{ $p->jenis === 'klien' ? 'text-success' : 'text-danger' }}">{{ rupiah($p->jumlah) }}</td>
                            <td class="small text-secondary td-wrap col-opsional-2">{{ \Illuminate\Support\Str::limit($p->keterangan, 40) ?: '-' }}</td>
                            <td class="text-end">
                                @include('partials.tombol-aksi', [
                                    'edit' => route('pembayaran.edit', $p),
                                    'hapus' => route('pembayaran.destroy', $p),
                                    'nama' => 'pembayaran '.rupiah($p->jumlah).' ('.$p->nama_pihak.')',
                                ])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pembayarans->hasPages())
                <div class="card-body border-top">{{ $pembayarans->links() }}</div>
            @endif
        @endif
    </div>
@endsection
