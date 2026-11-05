@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $persen = fn ($v) => $mode === 'excel' ? round($v, 1) : angka($v, 1).'%';
    $html = $mode === 'html';
    $kolomSatuan = $mode === 'excel'; // Excel: satuan di kolom tersendiri
    $kiri = 5 + ($kolomSatuan ? 1 : 0);
    $kolom = $kiri + ($opsi->beli() ? 1 : 0) + ($opsi->jual() ? 1 : 0) + ($opsi->laba() ? 2 : 0);
    $w = isset($lebarKolom) ? $lebarKolom($kolom - 1, 1) : ''; // PDF: kolom selain No dibagi rata
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@include('laporan._judul-ringkasan', ['adaRincian' => false, 'kolom' => $kolom])
<table class="laporan-table {{ $html ? 'table align-middle' : 'tabel' }}">
    <thead>
    <tr>
        <th class="kol-no">No</th>
        <th {!! $w !!}>Barang</th>
        <th {!! $w !!}>Supplier</th>
        <th {!! $w !!}>Jumlah Nota</th>
        <th {!! $w !!}>Total Qty</th>
        @if ($kolomSatuan) <th {!! $w !!}>Satuan</th> @endif
        @if ($opsi->beli()) <th {!! $w !!}>Total Beli</th> @endif
        @if ($opsi->jual()) <th {!! $w !!}>Total Jual</th> @endif
        @if ($opsi->laba())
            <th {!! $w !!}>Laba</th>
            <th {!! $w !!}>Margin{{ $mode === 'excel' ? ' (%)' : '' }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td {!! sel_warna($r->supplier?->warna) !!}>{{ $r->nama }}</td>
            <td><x-supplier :nama="$r->supplier?->nama" :warna="$r->supplier?->warna" :mode="$mode" /></td>
            <td>{{ $r->nota }}</td>
            <td>{{ $opsi->qty($r->qty, $r->satuan, $mode) }}</td>
            @if ($kolomSatuan) <td>{{ $r->satuan }}</td> @endif
            @if ($opsi->beli()) <td>{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td>{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba())
                <td class="{{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td>
                <td>{{ $persen($r->margin) }}</td>
            @endif
        </tr>
    @endforeach
    </tbody>
    @if ($opsi->adaHarga())
    <tfoot>
    <tr class="{{ $html ? 'table-light' : 'total' }}">
        <td></td>
        <td colspan="{{ $kiri - 1 }}"><b>TOTAL</b></td>
        @if ($opsi->beli()) <td><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba())
            <td><b>{{ $uang($total->laba) }}</b></td>
            <td><b>{{ $persen($total->margin) }}</b></td>
        @endif
    </tr>
    </tfoot>
    @endif
</table>
@include('laporan._penutup-ringkasan')

@include('laporan._legenda', ['legenda' => $rows->pluck('supplier')])

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Kolom <b>Supplier</b> adalah supplier langganan barang (dari menu Barang). Filter supplier menghitung item yang benar-benar dibeli dari supplier tersebut.
    </p>
@endif
