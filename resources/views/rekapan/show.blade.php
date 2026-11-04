@extends('layouts.app')

@section('title', 'Nota '.$transaksi->klien->nama)
@section('subtitle', tanggal($transaksi->tanggal, 'l, d M Y'))

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3 page-actions">
        <a href="{{ $kembali }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('rekapan.cetak', $transaksi) }}" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i> Cetak</a>
            {{-- PDF: lihat dulu atau langsung simpan --}}
            <div class="btn-group">
                <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" target="_blank" href="{{ route('rekapan.cetak', [$transaksi, 'pdf' => 1]) }}"><i class="bi bi-eye me-2"></i>Lihat PDF <span class="small text-secondary">(tab baru)</span></a></li>
                    <li><a class="dropdown-item" href="{{ route('rekapan.cetak', [$transaksi, 'pdf' => 1, 'unduh' => 1]) }}"><i class="bi bi-download me-2"></i>Unduh PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('rekapan.edit', [$transaksi, 'kembali' => $kembali]) }}" class="btn btn-light"><i class="bi bi-pencil-square me-1"></i> Edit</a>
            <form method="POST" action="{{ route('rekapan.destroy', $transaksi) }}" class="form-delete" data-nama="nota {{ $transaksi->klien->nama }} tanggal {{ tanggal($transaksi->tanggal) }}">
                @csrf @method('DELETE')
                <button class="btn btn-light text-danger"><i class="bi bi-trash3 me-1"></i> Hapus</button>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Item Barang</span>
                    <x-status-bayar :status="$transaksi->status_bayar" />
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Barang</th>
                            <th>Supplier</th>
                            <th class="num">Qty</th>
                            <th class="num">Harga Beli</th>
                            <th class="num">Harga Jual</th>
                            <th class="num">Subtotal Beli</th>
                            <th class="num">Subtotal Jual</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($transaksi->details as $d)
                            <tr>
                                <td class="text-secondary">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $d->barang->nama }}</td>
                                <td><x-supplier :nama="$d->supplier->nama" :warna="$d->supplier->warna" /></td>
                                <td class="num">{{ angka($d->qty) }} <span class="text-secondary small">{{ $d->barang->satuan }}</span></td>
                                <td class="num text-secondary">{{ rupiah($d->harga_beli) }}</td>
                                <td class="num">{{ rupiah($d->harga_jual) }}</td>
                                <td class="num text-secondary">{{ rupiah($d->subtotal_beli) }}</td>
                                <td class="num fw-semibold">{{ rupiah($d->subtotal_jual) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">Total</td>
                            <td class="num">{{ angka($transaksi->total_qty) }}</td>
                            <td colspan="2"></td>
                            <td class="num">{{ rupiah($transaksi->total_beli) }}</td>
                            <td class="num">{{ rupiah($transaksi->total_jual) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                @if ($transaksi->keterangan)
                    <div class="card-body border-top small"><span class="text-secondary">Keterangan:</span> {{ $transaksi->keterangan }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Ringkasan</div>
                <div class="card-body">
                    @foreach ([
                        ['Total Beli', rupiah($transaksi->total_beli), ''],
                        ['Total Jual', rupiah($transaksi->total_jual), 'fw-semibold'],
                        ['Laba', rupiah($transaksi->laba), $transaksi->laba < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'],
                    ] as [$l, $v, $c])
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">{{ $l }}</span><span class="{{ $c }}">{{ $v }}</span></div>
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Sudah Dibayar Klien</span><span class="text-success">{{ rupiah($dibayar) }}</span></div>
                    <div class="d-flex justify-content-between"><span class="fw-bold">Sisa Tagihan</span><span class="fw-bold {{ $sisa > 0 ? 'text-danger' : '' }}">{{ rupiah($sisa) }}</span></div>
                    @if ($sisa > 0)
                        <a href="{{ route('pembayaran.create', ['transaksi' => $transaksi->id]) }}" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran Klien
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Riwayat Pembayaran</div>
                <div class="card-body">
                    @forelse ($transaksi->pembayarans as $p)
                        <div class="rank-item">
                            <span class="rank-badge {{ $p->jenis === 'klien' ? 'text-success' : 'text-danger' }}" style="background:{{ $p->jenis === 'klien' ? '#dcfce7' : '#fee2e2' }}">
                                <i class="bi {{ $p->jenis === 'klien' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                            </span>
                            <div class="flex-grow-1" style="min-width:0">
                                <div class="fw-semibold small text-truncate">{{ $p->jenis === 'klien' ? 'Dari' : 'Ke' }} {{ $p->nama_pihak }}</div>
                                <div class="small text-secondary">{{ tanggal($p->tanggal) }} · {{ ucfirst($p->metode) }}</div>
                            </div>
                            <div class="num fw-semibold">{{ rupiah($p->jumlah) }}</div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-wallet2" title="Belum ada pembayaran" class="py-2" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
