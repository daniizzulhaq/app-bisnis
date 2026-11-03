<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $transaksi->klien->nama }} {{ tanggal($transaksi->tanggal) }}</title>
    {{-- CSS sederhana agar kompatibel dengan dompdf & cetak browser. Nota klien TIDAK menampilkan harga beli/supplier. --}}
    <style>
        * { box-sizing: border-box; }
        body { font-family: {{ $mode === 'pdf' ? "'DejaVu Sans', sans-serif" : "'Inter', Arial, sans-serif" }}; font-size: 11px; color: #1f2333; margin: 0; }
        .nota { max-width: 720px; margin: 0 auto; padding: 24px; }
        .kop { width: 100%; border-bottom: 2px solid #1f2333; padding-bottom: 10px; margin-bottom: 14px; }
        .kop td { vertical-align: top; }
        .brand { font-size: 18px; font-weight: bold; }
        .muted { color: #6b6e85; }
        .judul { font-size: 15px; font-weight: bold; letter-spacing: 2px; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { background: #f1f3f9; text-align: left; padding: 7px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #d9dce6; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #eceef4; }
        .r { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; border-top: 2px solid #1f2333; border-bottom: 0; }
        .ttd { width: 100%; margin-top: 36px; }
        .ttd td { text-align: center; width: 50%; }
        .garis { margin: 50px auto 0; width: 160px; border-top: 1px solid #1f2333; }
        .toolbar { text-align: center; padding: 12px; background: #f5f6fa; }
        .toolbar button, .toolbar a { font: inherit; padding: 8px 16px; border-radius: 8px; border: 1px solid #d9dce6; background: #fff; cursor: pointer; text-decoration: none; color: #1f2333; margin: 0 4px; }
        .toolbar .utama { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        @media print { .toolbar { display: none; } .nota { padding: 0; } }
    </style>
</head>
<body>
@if ($mode === 'html')
    <div class="toolbar">
        <button class="utama" onclick="window.print()">Cetak</button>
        <a href="{{ route('rekapan.cetak', [$transaksi, 'pdf' => 1]) }}" target="_blank">Lihat PDF</a>
        <a href="{{ route('rekapan.cetak', [$transaksi, 'pdf' => 1, 'unduh' => 1]) }}">Unduh PDF</a>
        <a href="{{ route('rekapan.show', $transaksi) }}">Kembali</a>
    </div>
@endif

<div class="nota">
    <table class="kop">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">Supplier Sayur Segar untuk Hotel &amp; Resto</div>
            </td>
            <td>
                <div class="judul">NOTA</div>
            </td>
        </tr>
    </table>

    <table style="width:100%">
        <tr>
            <td style="width:60%">
                <div class="muted">Kepada Yth.</div>
                <div style="font-weight:bold; font-size:13px">{{ $transaksi->klien->nama }}</div>
                @if ($transaksi->klien->alamat)<div>{{ $transaksi->klien->alamat }}</div>@endif
                @if ($transaksi->klien->no_hp)<div>Telp. {{ $transaksi->klien->no_hp }}</div>@endif
            </td>
            <td class="r" style="vertical-align:top">
                <div class="muted">Tanggal</div>
                <div style="font-weight:bold">{{ tanggal($transaksi->tanggal) }}</div>
                <div class="muted" style="margin-top:6px">Status</div>
                <div style="font-weight:bold">{{ \App\Models\Transaksi::STATUS[$transaksi->status_bayar] }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Barang</th>
            <th class="r">Qty</th>
            <th class="r">Harga</th>
            <th class="r">Jumlah</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($transaksi->details as $d)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $d->barang->nama }}</td>
                <td class="r">{{ angka($d->qty) }} {{ $d->barang->satuan }}</td>
                <td class="r">{{ rupiah($d->harga_jual) }}</td>
                <td class="r">{{ rupiah($d->subtotal_jual) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="4" class="r">TOTAL</td>
            <td class="r">{{ rupiah($transaksi->total_jual) }}</td>
        </tr>
        @if ($dibayar > 0)
            <tr><td colspan="4" class="r" style="border:0">Sudah dibayar</td><td class="r" style="border:0">{{ rupiah($dibayar) }}</td></tr>
            <tr><td colspan="4" class="r" style="border:0; font-weight:bold">Sisa tagihan</td><td class="r" style="border:0; font-weight:bold">{{ rupiah(max(0, $transaksi->total_jual - $dibayar)) }}</td></tr>
        @endif
        </tbody>
    </table>

    @if ($transaksi->keterangan)
        <p class="muted" style="margin-top:12px">Catatan: {{ $transaksi->keterangan }}</p>
    @endif

    <table class="ttd">
        <tr>
            <td>Penerima<div class="garis"></div></td>
            <td>Hormat kami<div class="garis"></div></td>
        </tr>
    </table>
</div>
</body>
</html>
