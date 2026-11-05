@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $html = $mode === 'html';
    $hutang = $opsi->beli(); // kolom dibayar & sisa hutang relevan bila harga beli ditampilkan
    $kolom = 4 + ($opsi->beli() ? 1 : 0) + ($opsi->jual() ? 1 : 0) + ($opsi->laba() ? 1 : 0) + ($hutang ? 2 : 0);
    $w = isset($lebarKolom) ? $lebarKolom($kolom - 1, 1) : ''; // PDF: kolom selain No dibagi rata
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

{{-- Rincian barang per supplier di atas, jumlah keseluruhan paling bawah --}}
@include('laporan._rincian', ['kolomPihak' => $opsi->tampilKlien ? 'klien' : null, 'judulRincian' => 'Barang dari'])

@include('laporan._judul-ringkasan', ['adaRincian' => $rincian->isNotEmpty()])
<table class="laporan-table {{ $html ? 'table align-middle' : 'tabel' }}" style="--jumlah-kolom:{{ $kolom }}">
    <thead>
    <tr>
        <th class="kol-no">No</th>
        <th {!! $w !!}>Supplier</th>
        <th {!! $w !!}>Jumlah Nota</th>
        <th {!! $w !!}>Total Qty</th>
        @if ($opsi->beli()) <th {!! $w !!}>Total Beli</th> @endif
        @if ($opsi->jual()) <th {!! $w !!}>Total Jual</th> @endif
        @if ($opsi->laba()) <th {!! $w !!}>Laba</th> @endif
        @if ($hutang)
            <th {!! $w !!}>Dibayar (periode)</th>
            <th {!! $w !!}>Sisa Hutang s/d {{ tanggal($periode->sampai) }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td {!! sel_warna($r->warna) !!}>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['supplier_id' => [$r->id]]) }}" class="text-decoration-none" title="Lihat rincian">
                        <x-supplier :nama="$r->nama" :warna="$r->warna" />
                    </a>
                @else
                    <x-supplier :nama="$r->nama" :warna="$r->warna" :mode="$mode" />
                @endif
            </td>
            <td>{{ $r->nota }}</td>
            <td>{{ $opsi->qty($r->qty, null, $mode) }}</td>
            @if ($opsi->beli()) <td>{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td>{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba()) <td class="{{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td> @endif
            @if ($hutang)
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
        <td></td>
        <td><b>{{ $opsi->qty($total->qty, null, $mode) }}</b></td>
        @if ($opsi->beli()) <td><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba()) <td><b>{{ $uang($total->laba) }}</b></td> @endif
        @if ($hutang)
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
        Rincian barang tiap supplier ditampilkan di atas, jumlah keseluruhan di bagian paling bawah. Klik nama supplier untuk melihat rincian satu supplier saja.
        Total qty menjumlahkan semua satuan.
        @if ($hutang)
            <br><b>Dibayar</b> = pembayaran ke supplier di dalam periode. <b>Sisa hutang</b> = seluruh pembelian dikurangi seluruh pembayaran sampai tanggal akhir periode
            (dihitung per supplier, tidak terpengaruh filter klien).
        @endif
    </p>
@endif
