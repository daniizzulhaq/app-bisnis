@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $html = $mode === 'html';
    $piutang = $opsi->jual(); // kolom dibayar & sisa piutang relevan bila harga jual ditampilkan
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@if ($html) <div class="card mb-3"><div class="card-header">Ringkasan per Klien</div><div class="table-responsive"> @endif
<table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
    <thead>
    <tr>
        <th>No</th>
        <th>Klien</th>
        <th class="num">Jumlah Nota</th>
        @if ($opsi->beli()) <th class="num">Total Beli</th> @endif
        @if ($opsi->jual()) <th class="num">Total Jual</th> @endif
        @if ($opsi->laba()) <th class="num">Laba</th> @endif
        @if ($piutang)
            <th class="num">Dibayar (periode)</th>
            <th class="num">Sisa Piutang s/d {{ tanggal($periode->sampai) }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['klien_id' => [$r->id]]) }}" class="fw-semibold text-decoration-none" title="Lihat rincian">{{ $r->nama }}</a>
                @else
                    {{ $r->nama }}
                @endif
            </td>
            <td class="num">{{ $r->nota }}</td>
            @if ($opsi->beli()) <td class="num">{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td class="num">{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba()) <td class="num {{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td> @endif
            @if ($piutang)
                <td class="num">{{ $uang($r->dibayar) }}</td>
                <td class="num {{ $html && $r->sisa > 0 ? 'text-danger fw-semibold' : '' }}">{{ $uang($r->sisa) }}</td>
            @endif
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
        <td></td>
        <td><b>TOTAL</b></td>
        <td class="num"><b>{{ $total->nota }}</b></td>
        @if ($opsi->beli()) <td class="num"><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td class="num"><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba()) <td class="num"><b>{{ $uang($total->laba) }}</b></td> @endif
        @if ($piutang)
            <td class="num"><b>{{ $uang($total->dibayar) }}</b></td>
            <td class="num"><b>{{ $uang($total->sisa) }}</b></td>
        @endif
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif

{{-- Supplier hanya tampil bila harga beli ikut ditampilkan (laporan internal) --}}
@include('laporan._rincian', ['kolomPihak' => $opsi->beli() ? 'supplier' : null, 'judulRincian' => 'Rincian Barang'])

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Klik nama klien untuk melihat rincian barang. Pilih beberapa klien di filter untuk menampilkan rinciannya sekaligus.
        @if ($piutang)
            <br><b>Dibayar</b> = pembayaran klien di dalam periode. <b>Sisa piutang</b> = seluruh penjualan dikurangi seluruh pembayaran sampai tanggal akhir periode
            (dihitung per klien, tidak terpengaruh filter supplier).
        @endif
    </p>
@endif
