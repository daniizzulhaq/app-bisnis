@props(['status'])

@php
    $ikon = ['lunas' => 'bi-check-circle-fill', 'sebagian' => 'bi-hourglass-split', 'belum' => 'bi-x-circle-fill'][$status] ?? 'bi-circle';
@endphp

<span {{ $attributes->merge(['class' => "badge badge-{$status}"]) }}>
    <i class="bi {{ $ikon }} me-1"></i>{{ \App\Models\Transaksi::STATUS[$status] ?? ucfirst($status) }}
</span>
