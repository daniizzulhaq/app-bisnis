{{--
    Pembuka tabel jumlah keseluruhan (paling bawah, setelah rincian). Variabel: $mode, $adaRincian, $kolom
    Ditutup dengan @include('laporan._penutup-ringkasan').
--}}
@if ($mode === 'excel')
    @if ($adaRincian) @include('laporan._spasi') @endif
    <table class="judul-xl"><tr><td colspan="{{ $kolom }}" style="font-size: 14px; font-weight: bold; height: 0.9cm"><b>Jumlah Keseluruhan</b></td></tr></table>
@else
    <div class="laporan-blok">
    <div class="laporan-judul">Jumlah Keseluruhan</div>
    @if ($mode === 'html') <div class="card"><div class="table-responsive laporan-scroll"> @endif
@endif
