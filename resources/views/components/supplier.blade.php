{{--
    Nama supplier dengan penanda warna.
    <x-supplier :nama="$s->nama" :warna="$s->warna" />  (mode: html | pdf | excel)
--}}
@props(['nama' => null, 'warna' => null, 'mode' => 'html'])
@php $warna = $warna ?: '#94a3b8'; @endphp
@if (! $nama)
    <span class="text-secondary">-</span>
@elseif ($mode === 'excel')
    {{ $nama }}
@elseif ($mode === 'pdf')
    <span style="display:inline-block;width:8px;height:8px;border-radius:4px;background:{{ $warna }};margin-right:3px"></span>{{ $nama }}
@else
    <span {{ $attributes->merge(['class' => 'sup-tag']) }} style="--sw: {{ $warna }}"><i></i>{{ $nama }}</span>
@endif
