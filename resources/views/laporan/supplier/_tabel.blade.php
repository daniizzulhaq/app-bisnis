@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $qty = fn ($v) => $mode === 'excel' ? round((float) $v, 2) : angka($v);
    $html = $mode === 'html';
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $supplier ? ' · '.$supplier->nama : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@if ($html) <div class="card mb-3"><div class="card-header">Ringkasan Hutang per Supplier</div><div class="table-responsive"> @endif
<table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
    <thead>
    <tr>
        <th>No</th>
        <th>Supplier</th>
        <th class="num">Jumlah Nota</th>
        <th class="num">Total Qty</th>
        <th class="num">Total Beli</th>
        <th class="num">Dibayar (periode)</th>
        <th class="num">Sisa Hutang s/d {{ tanggal($periode->sampai) }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['supplier_id' => $r->id]) }}" class="fw-semibold text-decoration-none">{{ $r->nama }}</a>
                @else
                    {{ $r->nama }}
                @endif
            </td>
            <td class="num">{{ $r->nota }}</td>
            <td class="num">{{ $qty($r->qty) }}</td>
            <td class="num">{{ $uang($r->total_beli) }}</td>
            <td class="num">{{ $uang($r->dibayar) }}</td>
            <td class="num {{ $html && $r->sisa > 0 ? 'text-danger fw-semibold' : '' }}">{{ $uang($r->sisa) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
        <td></td>
        <td><b>TOTAL</b></td>
        <td class="num"></td>
        <td class="num"><b>{{ $qty($total->qty) }}</b></td>
        <td class="num"><b>{{ $uang($total->total_beli) }}</b></td>
        <td class="num"><b>{{ $uang($total->dibayar) }}</b></td>
        <td class="num"><b>{{ $uang($total->sisa) }}</b></td>
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif

@if ($supplier)
    @if ($mode === 'excel')
        <table><tr><td></td></tr><tr><td><b>Item yang dibeli dari {{ $supplier->nama }}</b></td></tr></table>
    @elseif ($mode === 'pdf')
        <h3>Item yang dibeli dari {{ $supplier->nama }}</h3>
    @else
        <div class="card"><div class="card-header">Item yang Dibeli · {{ $supplier->nama }}</div><div class="table-responsive">
    @endif

    <table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
        <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>No. Nota</th>
            <th>Untuk Klien</th>
            <th>Barang</th>
            <th class="num">Qty</th>
            <th>Satuan</th>
            <th class="num">Harga Beli</th>
            <th class="num">Subtotal Beli</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ tanggal($d->transaksi->tanggal) }}</td>
                <td>
                    @if ($html)
                        <a href="{{ route('rekapan.show', $d->transaksi_id) }}" class="text-decoration-none">{{ $d->transaksi->no_nota }}</a>
                    @else
                        {{ $d->transaksi->no_nota }}
                    @endif
                </td>
                <td>{{ $d->transaksi->klien->nama }}</td>
                <td>{{ $d->barang->nama }}</td>
                <td class="num">{{ $qty($d->qty) }}</td>
                <td>{{ $d->barang->satuan }}</td>
                <td class="num">{{ $uang($d->harga_beli) }}</td>
                <td class="num">{{ $uang($d->subtotal_beli) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center">Tidak ada pembelian pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if ($items->isNotEmpty())
            <tfoot>
            <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
                <td></td>
                <td colspan="7"><b>TOTAL</b></td>
                <td class="num"><b>{{ $uang($items->sum('subtotal_beli')) }}</b></td>
            </tr>
            </tfoot>
        @endif
    </table>
    @if ($html) </div></div> @endif
@endif

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        <b>Dibayar</b> = pembayaran ke supplier di dalam periode. <b>Sisa hutang</b> = seluruh pembelian dikurangi seluruh pembayaran sampai tanggal akhir periode.
        Klik nama supplier untuk melihat rincian item.
    </p>
@endif
