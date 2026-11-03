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

@if ($rows->isEmpty())
    <p>Tidak ada data pada periode ini.</p>
@else
    @include("laporan.{$jenis}._tabel")
@endif

<div class="footer">Dicetak {{ tanggal(now(), 'd M Y H:i') }}</div>
</body>
</html>
