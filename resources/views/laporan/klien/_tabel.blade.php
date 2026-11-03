@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $html = $mode === 'html';
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $klien ? ' · '.$klien->nama : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@if ($html) <div class="card mb-3"><div class="card-header">Ringkasan Piutang per Klien</div><div class="table-responsive"> @endif
<table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
    <thead>
    <tr>
        <th>No</th>
        <th>Klien</th>
        <th class="num">Jumlah Nota</th>
        <th class="num">Total Jual</th>
        <th class="num">Dibayar (periode)</th>
        <th class="num">Sisa Piutang s/d {{ tanggal($periode->sampai) }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['klien_id' => $r->id]) }}" class="fw-semibold text-decoration-none">{{ $r->nama }}</a>
                @else
                    {{ $r->nama }}
                @endif
            </td>
            <td class="num">{{ $r->nota }}</td>
            <td class="num">{{ $uang($r->total_jual) }}</td>
            <td class="num">{{ $uang($r->dibayar) }}</td>
            <td class="num {{ $html && $r->sisa > 0 ? 'text-danger fw-semibold' : '' }}">{{ $uang($r->sisa) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
        <td></td>
        <td><b>TOTAL</b></td>
        <td class="num"><b>{{ $total->nota }}</b></td>
        <td class="num"><b>{{ $uang($total->total_jual) }}</b></td>
        <td class="num"><b>{{ $uang($total->dibayar) }}</b></td>
        <td class="num"><b>{{ $uang($total->sisa) }}</b></td>
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif

@if ($klien)
    @if ($mode === 'excel')
        <table><tr><td></td></tr><tr><td><b>Daftar Nota {{ $klien->nama }}</b></td></tr></table>
    @elseif ($mode === 'pdf')
        <h3>Daftar Nota {{ $klien->nama }}</h3>
    @else
        <div class="card"><div class="card-header">Daftar Nota · {{ $klien->nama }}</div><div class="table-responsive">
    @endif

    <table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
        <thead>
        <tr>
            <th>No</th>
            <th>No. Nota</th>
            <th>Tanggal</th>
            <th class="num">Total Jual</th>
            <th class="num">Sudah Dibayar</th>
            <th class="num">Sisa</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($notas as $n)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    @if ($html)
                        <a href="{{ route('rekapan.show', $n) }}" class="text-decoration-none">{{ $n->no_nota }}</a>
                    @else
                        {{ $n->no_nota }}
                    @endif
                </td>
                <td>{{ tanggal($n->tanggal) }}</td>
                <td class="num">{{ $uang($n->total_jual) }}</td>
                <td class="num">{{ $uang($n->dibayar) }}</td>
                <td class="num">{{ $uang(max(0, $n->total_jual - $n->dibayar)) }}</td>
                <td>
                    @if ($html) <x-status-bayar :status="$n->status_bayar" /> @else {{ \App\Models\Transaksi::STATUS[$n->status_bayar] }} @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center">Tidak ada nota pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if ($notas->isNotEmpty())
            <tfoot>
            <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
                <td></td>
                <td colspan="2"><b>TOTAL</b></td>
                <td class="num"><b>{{ $uang($notas->sum('total_jual')) }}</b></td>
                <td class="num"><b>{{ $uang($notas->sum('dibayar')) }}</b></td>
                <td class="num"><b>{{ $uang($notas->sum(fn ($n) => max(0, $n->total_jual - $n->dibayar))) }}</b></td>
                <td></td>
            </tr>
            </tfoot>
        @endif
    </table>
    @if ($html) </div></div> @endif
@endif

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        <b>Dibayar</b> = pembayaran klien di dalam periode. <b>Sisa piutang</b> = seluruh penjualan dikurangi seluruh pembayaran sampai tanggal akhir periode.
        Klik nama klien untuk melihat rincian nota.
    </p>
@endif
