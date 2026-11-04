@php
    // html/pdf: format Rupiah; excel: angka mentah agar bisa dihitung di Excel
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    $html = $mode === 'html';
    $hutang = $opsi->beli(); // kolom dibayar & sisa hutang relevan bila harga beli ditampilkan
@endphp

@if ($mode === 'excel')
    <table>
        <tr><td><b>{{ $judul }}</b></td></tr>
        <tr><td>Periode: {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}</td></tr>
        <tr><td></td></tr>
    </table>
@endif

@if ($html) <div class="card mb-3"><div class="card-header">Ringkasan per Supplier</div><div class="table-responsive laporan-scroll"> @endif
<table class="{{ $html ? 'table table-hover align-middle laporan-table' : 'tabel laporan-table' }}">
    <thead>
    <tr>
        <th>No</th>
        <th>Supplier</th>
        <th class="num">Jumlah Nota</th>
        <th class="num">Total Qty</th>
        @if ($opsi->beli()) <th class="num">Total Beli</th> @endif
        @if ($opsi->jual()) <th class="num">Total Jual</th> @endif
        @if ($opsi->laba()) <th class="num">Laba</th> @endif
        @if ($hutang)
            <th class="num">Dibayar (periode)</th>
            <th class="num">Sisa Hutang s/d {{ tanggal($periode->sampai) }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td {!! sel_warna($r->warna) !!}>
                @if ($html)
                    <a href="{{ request()->fullUrlWithQuery(['supplier_id' => [$r->id]]) }}" class="text-decoration-none fw-semibold" title="Lihat rincian">
                        <x-supplier :nama="$r->nama" :warna="$r->warna" />
                    </a>
                @else
                    <x-supplier :nama="$r->nama" :warna="$r->warna" :mode="$mode" />
                @endif
            </td>
            <td class="num">{{ $r->nota }}</td>
            <td class="num">{{ $opsi->qty($r->qty, null, $mode) }}</td>
            @if ($opsi->beli()) <td class="num">{{ $uang($r->total_beli) }}</td> @endif
            @if ($opsi->jual()) <td class="num">{{ $uang($r->total_jual) }}</td> @endif
            @if ($opsi->laba()) <td class="num {{ $html ? ($r->laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($r->laba) }}</td> @endif
            @if ($hutang)
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
        <td class="num"></td>
        <td class="num"><b>{{ $opsi->qty($total->qty, null, $mode) }}</b></td>
        @if ($opsi->beli()) <td class="num"><b>{{ $uang($total->total_beli) }}</b></td> @endif
        @if ($opsi->jual()) <td class="num"><b>{{ $uang($total->total_jual) }}</b></td> @endif
        @if ($opsi->laba()) <td class="num"><b>{{ $uang($total->laba) }}</b></td> @endif
        @if ($hutang)
            <td class="num"><b>{{ $uang($total->dibayar) }}</b></td>
            <td class="num"><b>{{ $uang($total->sisa) }}</b></td>
        @endif
    </tr>
    </tfoot>
</table>
@if ($html) </div></div> @endif

@include('laporan._rincian', ['kolomPihak' => $opsi->tampilKlien ? 'klien' : null, 'judulRincian' => 'Barang dari'])

@if ($html)
    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Klik nama supplier untuk melihat rincian barang. Pilih beberapa supplier di filter untuk menampilkan rinciannya sekaligus.
        Total qty menjumlahkan semua satuan.
        @if ($hutang)
            <br><b>Dibayar</b> = pembayaran ke supplier di dalam periode. <b>Sisa hutang</b> = seluruh pembelian dikurangi seluruh pembayaran sampai tanggal akhir periode
            (dihitung per supplier, tidak terpengaruh filter klien).
        @endif
    </p>
@endif
