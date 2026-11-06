{{--
    Rincian item per pihak terpilih (dipakai laporan klien & supplier). Ditampilkan DI ATAS tabel jumlah keseluruhan.
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
    // PDF: kolom selain No & Tanggal dibagi rata
    $w = isset($lebarKolom) ? $lebarKolom($kolomTotal - 2, 4.5) : '';
@endphp

@foreach ($rincian as $grup)
    @if ($mode === 'excel')
        @unless ($loop->first) @include('laporan._spasi') @endunless
        <table class="judul-xl"><tr><td colspan="{{ $kolomTotal }}" style="font-family: Times New Roman; font-size: 16px; font-weight: bold; height: 0.9cm; text-align: center"><b>{{ $judulRincian }} {{ $grup->nama }}</b></td></tr></table>
    @else
        <div class="laporan-blok">
        <div class="laporan-judul">{{ $judulRincian }} {{ $grup->nama }}</div>
        @if ($html) <div class="card"><div class="table-responsive laporan-scroll"> @endif
    @endif

    <table class="laporan-table {{ $html ? 'table align-middle' : 'tabel' }}" style="--jumlah-kolom:{{ $kolomTotal }}">
        <thead>
        <tr>
            <th class="kol-no">No</th>
            <th class="kol-tgl">Tanggal</th>
            @if ($kolomPihak) <th {!! $w !!}>{{ $kolomPihak === 'klien' ? 'Klien' : 'Supplier' }}</th> @endif
            <th {!! $w !!}>Barang</th>
            <th {!! $w !!}>Qty</th>
            @if ($kolomSatuan) <th {!! $w !!}>Satuan</th> @endif
            @if ($opsi->beli()) <th {!! $w !!}>Harga Beli</th><th {!! $w !!}>Subtotal Beli</th> @endif
            @if ($opsi->jual()) <th {!! $w !!}>Harga Jual</th><th {!! $w !!}>Subtotal Jual</th> @endif
            @if ($opsi->laba()) <th {!! $w !!}>Laba</th> @endif
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
                <td>{{ $opsi->qty($d->qty, $d->barang->satuan, $mode, $d->pecahan ?? []) }}</td>
                @if ($kolomSatuan) <td>{{ $d->barang->satuan }}</td> @endif
                @if ($opsi->beli())
                    <td>{{ $harga($d->harga_beli, $d->harga_beli_list) }}</td>
                    <td>{{ $uang($d->subtotal_beli) }}</td>
                @endif
                @if ($opsi->jual())
                    <td>{{ $harga($d->harga_jual, $d->harga_jual_list) }}</td>
                    <td>{{ $uang($d->subtotal_jual) }}</td>
                @endif
                @if ($opsi->laba())
                    @php $laba = $d->subtotal_jual - $d->subtotal_beli; @endphp
                    <td class="{{ $html ? ($laba < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $uang($laba) }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $kolomTotal }}">Tidak ada item pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if ($grup->items->isNotEmpty() && $opsi->adaHarga())
            <tfoot>
            <tr class="{{ $html ? 'table-light' : 'total' }}">
                <td colspan="{{ $kiri }}"><b>TOTAL</b></td>
                @if ($opsi->beli()) <td></td><td><b>{{ $uang($grup->items->sum('subtotal_beli')) }}</b></td> @endif
                @if ($opsi->jual()) <td></td><td><b>{{ $uang($grup->items->sum('subtotal_jual')) }}</b></td> @endif
                @if ($opsi->laba()) <td><b>{{ $uang($grup->items->sum('subtotal_jual') - $grup->items->sum('subtotal_beli')) }}</b></td> @endif
            </tr>
            </tfoot>
        @endif
    </table>
    @if ($html) </div></div> @endif
    @if ($mode !== 'excel') </div> @endif
@endforeach
