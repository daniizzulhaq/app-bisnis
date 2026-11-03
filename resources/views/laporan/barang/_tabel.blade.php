@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $qty = fn ($v) => $mode === 'excel' ? round((float) $v, 2) : angka($v);
    $persen = fn ($v) => $mode === 'excel' ? round($v, 1) : angka($v, 1).'%';
    $html = $mode === 'html';
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@if ($html) <div class="card"><div class="table-responsive"> @endif
<table class="{{ $html ? 'table table-hover align-middle' : 'tabel' }}">
    <thead>
    <tr>
        <th>No</th>
        <th>Barang</th>
        <th>Satuan</th>
        <th class="num">Jumlah Nota</th>
        <th class="num">Total Qty</th>
        <th class="num">Total Beli</th>
        <th class="num">Total Jual</th>
        <th class="num">Laba</th>
        <th class="num">Margin{{ $mode === 'excel' ? ' (%)' : '' }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="{{ $html ? 'fw-semibold' : '' }}">{{ $r->nama }}</td>
            <td>{{ $r->satuan }}</td>
            <td class="num">{{ $r->nota }}</td>
            <td class="num">{{ $qty($r->qty) }}</td>
            <td class="num">{{ $uang($r->total_beli) }}</td>
            <td class="num">{{ $uang($r->total_jual) }}</td>
            <td class="num {{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td>
            <td class="num">{{ $persen($r->margin) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
        <td></td>
        <td colspan="4"><b>TOTAL</b></td>
        <td class="num"><b>{{ $uang($total->total_beli) }}</b></td>
        <td class="num"><b>{{ $uang($total->total_jual) }}</b></td>
        <td class="num"><b>{{ $uang($total->laba) }}</b></td>
        <td class="num"><b>{{ $persen($total->margin) }}</b></td>
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif
