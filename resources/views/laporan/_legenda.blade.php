{{--
    Keterangan warna supplier di bawah tabel. Variabel: $legenda (koleksi supplier: nama, warna), $mode
--}}
@php $legenda = collect($legenda)->filter()->unique('id')->sortBy('nama')->values(); @endphp
@if ($legenda->isNotEmpty())
    @if ($mode === 'excel')
        <table>
            <tr><td><b>Keterangan warna supplier</b></td></tr>
            @foreach ($legenda as $s)
                <tr><td {!! sel_warna($s->warna) !!}>{{ $s->nama }}</td></tr>
            @endforeach
        </table>
    @elseif ($mode === 'pdf')
        <div class="legenda">
            <b>Keterangan warna:</b>
            @foreach ($legenda as $s)
                <span class="legenda-item"><span class="legenda-kotak" style="background: {{ warna_lembut($s->warna) }}; border-color: {{ $s->warna }}"></span>{{ $s->nama }}</span>
            @endforeach
        </div>
    @else
        <div class="legenda-warna small text-secondary mb-3">
            <span class="fw-semibold me-1">Keterangan warna:</span>
            @foreach ($legenda as $s)
                <span class="legenda-item"><span class="legenda-kotak" style="background: {{ warna_lembut($s->warna) }}; border-color: {{ $s->warna }}"></span>{{ $s->nama }}</span>
            @endforeach
        </div>
    @endif
@endif
