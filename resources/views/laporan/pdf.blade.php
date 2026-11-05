<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    {{-- CSS khusus dompdf (tanpa Bootstrap) --}}
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2333; }
        .kop { border-bottom: 2px solid #1f2333; padding-bottom: 8px; margin-bottom: 12px; }
        .kop .brand { font-size: 15px; font-weight: bold; }
        .kop .muted, .muted { color: #6b6e85; }
        h2 { font-size: 13px; margin: 0 0 2px; }
        h3 { font-size: 11px; margin: 16px 0 6px; }
        table.tabel { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.tabel th { background: #eef1fe; text-align: left; padding: 5px 6px; font-size: 8.5px; text-transform: uppercase; border: 1px solid #d9dce6; }
        table.tabel td { padding: 5px 6px; border: 1px solid #e5e7ef; }
        table.tabel .num { text-align: right; white-space: nowrap; }
        .legenda { font-size: 8.5px; color: #555; margin: 2px 0 10px; }
        .legenda-item { display: inline-block; margin: 0 10px 3px 0; white-space: nowrap; }
        .legenda-kotak { display: inline-block; width: 10px; height: 8px; border: 1px solid; margin-right: 3px; vertical-align: -1px; }
        table.tabel tr.total td { background: #f5f6fa; font-weight: bold; }
        /* Tabel laporan: semua tulisan & garis tebal, rata kiri, baris 0,75 cm */
        table.laporan-table th, table.laporan-table td {
            height: 0.75cm; padding: 1px 4px; vertical-align: middle; overflow-wrap: break-word;
            font-size: 9.5px; font-weight: bold; text-align: left; border: 1.5px solid #1f2333;
        }
        table.laporan-table th { background: #eef1fe; }
        table.laporan-table b { font-weight: bold; } /* cegah "bolder" yang jatuh ke font serif */
        table.laporan-table .num { white-space: normal; }
        /* Lebar = isi + padding & garis (+-0,25 cm): No total 1 cm, Tanggal total 3,5 cm */
        table.laporan-table th.kol-no { width: 0.75cm; }
        table.laporan-table th.kol-tgl { width: 3.25cm; }
        /* Nama klien/supplier di atas tabel: lebih besar dari isi tabel */
        .laporan-judul { font-size: 14px; font-weight: bold; margin: 0 0 6px; }
        /* Jarak antar tabel laporan tiap klien/supplier */
        .laporan-blok + .laporan-blok { margin-top: 3cm; }
        .laporan-blok + .legenda { margin-top: 8px; }
        table.laporan-table tr { page-break-inside: avoid; } /* baris tidak terpotong antar halaman */
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 8px; color: #8a8fa3; }
    </style>
</head>
<body>
<div class="kop">
    <div class="brand">{{ config('app.name') }}</div>
    <div class="muted">Supplier Sayur untuk Hotel &amp; Resto</div>
</div>

<h2>{{ $judul }}</h2>
<div class="muted" style="margin-bottom:10px">
    Periode {{ $periode->label() }}{{ $subjek ? ' · '.$subjek : '' }}
</div>

@php
    // Lebar kolom selain No (1 cm) & Tanggal (3,5 cm) dibagi rata, agar ukuran kolom seragam di setiap tabel
    // (0,25 cm = padding & garis tiap sel, tidak termasuk dalam "width")
    $lebarKolom = fn (int $jumlahKolom, float $lebarTetap) => 'style="width: '.round(($lebarIsi - $lebarTetap) / max(1, $jumlahKolom) - 0.25, 2).'cm"';
@endphp

@if ($rows->isEmpty())
    <p>Tidak ada data pada periode ini.</p>
@else
    @include("laporan.{$jenis}._tabel")
@endif

<div class="footer">Dicetak {{ tanggal(now(), 'd M Y H:i') }}</div>
</body>
</html>
