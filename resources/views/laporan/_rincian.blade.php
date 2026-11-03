{{--
    Rincian item per pihak terpilih (dipakai laporan klien & supplier).
    Variabel: $rincian, $opsi, $mode, $kolomPihak ('klien' | 'supplier' | null), $judulRincian
--}}
@php
    $uang = fn ($v) => $mode === 'excel' ? round((float) $v) : rupiah($v);
    // Baris gabungan dengan harga berbeda: "Rp 60.000 / Rp 70.000"
    $harga = fn ($v, $list) => count($list ?? []) > 1
        ? implode(' / ', array_map(fn ($h) => $mode === 'excel' ? angka($h, 0) : rupiah($h), $list))
        : $uang($v);
    $html = $mode === 'html';
    $kolomSatuan = $mode === 'excel'; // Excel: satuan di kolom tersendiri
    // Jumlah kolom sebelum kolom harga (untuk colspan baris total)
    $kiri = 4 + ($kolomPihak ? 1 : 0) + ($kolomSatuan ? 1 : 0);
    $kolomTotal = $kiri + ($opsi->beli() ? 2 : 0) + ($opsi->jual() ? 2 : 0) + ($opsi->laba() ? 1 : 0);
@endphp

@foreach ($rincian as $grup)
    @if ($mode === 'excel')
        <table><tr><td></td></tr><tr><td><b>{{ $judulRincian }} {{ $grup->nama }}</b></td></tr></table>
    @elseif ($mode === 'pdf')
        <h3>{{ $judulRincian }} {{ $grup->nama }}</h3>
    @else
        <div class="card mb-3"><div class="card-header">{{ $judulRincian }} · {{ $grup->nama }}</div><div class="table-responsive">
    @endif

    <table class="{{ $html ? 'table table-hover align-middle tabel-rapat' : 'tabel' }}">
        <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            @if ($kolomPihak) <th>{{ $kolomPihak === 'klien' ? 'Klien' : 'Supplier' }}</th> @endif
            <th>Barang</th>
            <th class="num">Qty</th>
            @if ($kolomSatuan) <th>Satuan</th> @endif
            @if ($opsi->beli()) <th class="num">Harga Beli</th><th class="num">Subtotal Beli</th> @endif
            @if ($opsi->jual()) <th class="num">Harga Jual</th><th class="num">Subtotal Jual</th> @endif
            @if ($opsi->laba()) <th class="num">Laba</th> @endif
        </tr>
        </thead>
        <tbody>
        @forelse ($grup->items as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    @if ($html)
                        <a href="{{ route('rekapan.show', $d->transaksi_id) }}" class="text-decoration-none">{{ tanggal($d->tanggal) }}</a>
                    @else
                        {{ tanggal($d->tanggal) }}
                    @endif
                </td>
                @if ($kolomPihak === 'klien') <td>{{ $d->transaksi->klien->nama }}</td> @endif
                @if ($kolomPihak === 'supplier') <td><x-supplier :nama="$d->supplier->nama" :warna="$d->supplier->warna" :mode="$mode" /></td> @endif
                <td {!! sel_warna($d->supplier?->warna) !!}>{{ $d->barang->nama }}</td>
                <td class="num">{{ $opsi->qty($d->qty, $d->barang->satuan, $mode, $d->pecahan ?? []) }}</td>
                @if ($kolomSatuan) <td>{{ $d->barang->satuan }}</td> @endif
                @if ($opsi->beli())
                    <td class="num">{{ $harga($d->harga_beli, $d->harga_beli_list) }}</td>
                    <td class="num">{{ $uang($d->subtotal_beli) }}</td>
                @endif
                @if ($opsi->jual())
                    <td class="num">{{ $harga($d->harga_jual, $d->harga_jual_list) }}</td>
                    <td class="num">{{ $uang($d->subtotal_jual) }}</td>
                @endif
                @if ($opsi->laba())
                    @php $laba = $d->subtotal_jual - $d->subtotal_beli; @endphp
                    <td class="num {{ $html ? ($laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($laba) }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $kolomTotal }}" class="text-center">Tidak ada item pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if ($grup->items->isNotEmpty() && $opsi->adaHarga())
            <tfoot>
            <tr class="{{ $html ? 'table-light fw-bold' : 'total' }}">
                <td colspan="{{ $kiri }}"><b>TOTAL</b></td>
                @if ($opsi->beli()) <td></td><td class="num"><b>{{ $uang($grup->items->sum('subtotal_beli')) }}</b></td> @endif
                @if ($opsi->jual()) <td></td><td class="num"><b>{{ $uang($grup->items->sum('subtotal_jual')) }}</b></td> @endif
                @if ($opsi->laba()) <td class="num"><b>{{ $uang($grup->items->sum('subtotal_jual') - $grup->items->sum('subtotal_beli')) }}</b></td> @endif
            </tr>
            </tfoot>
        @endif
    </table>
    @if ($html) </div></div> @endif
@endforeach

@if ($rincian->isNotEmpty())
    @include('laporan._legenda', ['legenda' => $rincian->flatMap(fn ($g) => $g->items->pluck('supplier'))])
@endif
