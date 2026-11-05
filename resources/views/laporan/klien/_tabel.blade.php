@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $html = $mode === 'html';
    $piutang = $opsi->jual(); // kolom dibayar & sisa piutang relevan bila harga jual ditampilkan
    $kolom = 3 + ($opsi->beli() ? 1 : 0) + ($opsi->jual() ? 1 : 0) + ($opsi->laba() ? 1 : 0) + ($piutang ? 2 : 0);
    $w = isset($lebarKolom) ? $lebarKolom($kolom - 1, 1) : ''; // PDF: kolom selain No dibagi rata
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

{{-- Rincian barang per klien di atas, jumlah keseluruhan paling bawah --}}
{{-- Supplier hanya tampil bila harga beli ikut ditampilkan (laporan internal) --}}
@include('laporan._rincian', ['kolomPihak' => $opsi->beli() ? 'supplier' : null, 'judulRincian' => 'Rincian Barang'])

@include('laporan._judul-ringkasan', ['adaRincian' => $rincian->isNotEmpty()])
<table class="laporan-table {{ $html ? 'table align-middle' : 'tabel' }}" style="--jumlah-kolom:{{ $kolom }}">
    <thead>
    <tr>
        <th class="kol-no">No</th>
        <th {!! $w !!}>Klien</th>
        <th {!! $w !!}>Jumlah Nota</th>
        @if ($opsi->beli()) <th {!! $w !!}>Total Beli</th> @endif
        @if ($opsi->jual()) <th {!! $w !!}>Total Jual</th> @endif
        @if ($opsi->laba()) <th {!! $w !!}>Laba</th> @endif
        @if ($piutang)
            <th {!! $w !!}>Dibayar (periode)</th>
            <th {!! $w !!}>Sisa Piutang s/d {{ tanggal($periode->sampai) }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['klien_id' => [$r->id]]) }}" class="text-decoration-none" title="Lihat rincian">{{ $r->nama }}</a>
                @else
                    {{ $r->nama }}
                @endif
            </td>
            <td>{{ $r->nota }}</td>
            @if ($opsi->beli()) <td>{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td>{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba()) <td class="{{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td> @endif
            @if ($piutang)
                <td>{{ $uang($r->dibayar) }}</td>
                <td class="{{ $html && $r->sisa > 0 ? 'text-danger' : '' }}">{{ $uang($r->sisa) }}</td>
            @endif
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light' : 'total' }}">
        <td></td>
        <td><b>TOTAL</b></td>
        <td><b>{{ $total->nota }}</b></td>
        @if ($opsi->beli()) <td><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba()) <td><b>{{ $uang($total->laba) }}</b></td> @endif
        @if ($piutang)
            <td><b>{{ $uang($total->dibayar) }}</b></td>
            <td><b>{{ $uang($total->sisa) }}</b></td>
        @endif
    </tr>
    </tfoot>
</table>
@include('laporan._penutup-ringkasan')

@if ($rincian->isNotEmpty())
    @include('laporan._legenda', ['legenda' => $rincian->flatMap(fn ($g) => $g->items->pluck('supplier'))])
@endif

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Rincian barang semua klien ditampilkan di atas, jumlah keseluruhan di bagian paling bawah. Pilih klien di filter untuk membatasi rincian.
        @if ($piutang)
            <br><b>Dibayar</b> = pembayaran klien di dalam periode. <b>Sisa piutang</b> = seluruh penjualan dikurangi seluruh pembayaran sampai tanggal akhir periode
            (dihitung per klien, tidak terpengaruh filter supplier).
        @endif
    </p>
@endif
