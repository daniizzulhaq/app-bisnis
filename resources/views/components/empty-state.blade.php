@props([
    'icon' => 'bi-inbox',
    'title' => 'Belum ada data',
    'message' => null,
])

{{-- Tampilan ramah ketika data masih kosong. Slot opsional untuk tombol aksi. --}}
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-state-icon"><i class="bi {{ $icon }}"></i></div>
    <h6>{{ $title }}</h6>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
