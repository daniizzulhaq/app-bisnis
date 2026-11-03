@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $persen = fn ($v) => $mode === 'excel' ? round($v, 1) : angka($v, 1).'%';
    $html = $mode === 'html';
    $kolomSatuan = $mode === 'excel' && $opsi->pakaiSatuan();
    $kiri = 5 + ($kolomSatuan ? 1 : 0);
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
        <th>Supplier</th>
        <th class="num">Jumlah Nota</th>
        <th class="num">Total Qty</th>
        @if ($kolomSatuan) <th>Satuan</th> @endif
        @if ($opsi->beli()) <th class="num">Total Beli</th> @endif
        @if ($opsi->jual()) <th class="num">Total Jual</th> @endif
        @if ($opsi->laba())
            <th class="num">Laba</th>
            <th class="num">Margin{{ $mode === 'excel' ? ' (%)' : '' }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="{{ $html ? 'fw-semibold' : '' }}">{{ $r->nama }}</td>
            <td><x-supplier :nama="$r->supplier?->nama" :warna="$r->supplier?->warna" :mode="$mode" /></td>
            <td class="num">{{ $r->nota }}</td>
            <td class="num">{{ $opsi->qty($r->qty, $r->satuan, $mode) }}</td>
            @if ($kolomSatuan) <td>{{ $r->satuan }}</td> @endif
            @if ($opsi->beli()) <td class="num">{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td class="num">{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba())
                <td class="num {{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td>
                <td class="num">{{ $persen($r->margin) }}</td>
            @endif
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
        <td></td>
        <td colspan="{{ $kiri - 1 }}"><b>TOTAL</b></td>
        @if ($opsi->beli()) <td class="num"><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td class="num"><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba())
            <td class="num"><b>{{ $uang($total->laba) }}</b></td>
            <td class="num"><b>{{ $persen($total->margin) }}</b></td>
        @endif
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Kolom <b>Supplier</b> adalah supplier langganan barang (dari menu Barang). Filter supplier menghitung item yang benar-benar dibeli dari supplier tersebut.
    </p>
@endif
