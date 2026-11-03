@php
    // Struktur menu sidebar. 'active' = pola nama route untuk menandai menu aktif.
    $menus = [
        ['heading' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'dashboard', 'active' => 'dashboard'],
        ]],
        ['heading' => 'Master Data', 'items' => [
            ['label' => 'Supplier', 'icon' => 'bi-truck', 'route' => 'supplier.index', 'active' => 'supplier.*'],
            ['label' => 'Barang', 'icon' => 'bi-basket2', 'route' => 'barang.index', 'active' => 'barang.*'],
            ['label' => 'Klien', 'icon' => 'bi-building', 'route' => 'klien.index', 'active' => 'klien.*'],
        ]],
        ['heading' => 'Transaksi', 'items' => [
            ['label' => 'Rekapan', 'icon' => 'bi-receipt', 'route' => 'rekapan.index', 'active' => 'rekapan.*'],
        ]],
        ['heading' => 'Laporan', 'items' => [
            ['label' => 'Per Klien', 'icon' => 'bi-person-lines-fill', 'route' => 'laporan.klien', 'active' => 'laporan.klien*'],
            ['label' => 'Per Supplier', 'icon' => 'bi-clipboard-data', 'route' => 'laporan.supplier', 'active' => 'laporan.supplier*'],
            ['label' => 'Per Barang', 'icon' => 'bi-bar-chart-line', 'route' => 'laporan.barang', 'active' => 'laporan.barang*'],
        ]],
        ['heading' => 'Keuangan', 'items' => [
            ['label' => 'Pembayaran', 'icon' => 'bi-credit-card-2-front', 'route' => 'pembayaran.index', 'active' => 'pembayaran.*'],
            ['label' => 'Cashflow', 'icon' => 'bi-wallet2', 'route' => 'cashflow.index', 'active' => 'cashflow.*'],
        ]],
        ['heading' => 'Pengaturan', 'items' => [
            ['label' => 'Riwayat Hapus', 'icon' => 'bi-trash3', 'route' => 'riwayat-hapus.index', 'active' => 'riwayat-hapus.*'],
            ['label' => 'User', 'icon' => 'bi-person-gear', 'route' => 'user.edit', 'active' => 'user.*'],
        ]],
    ];
    $user = auth()->user();
@endphp

{{-- offcanvas-lg: tampil tetap di desktop, menjadi offcanvas (hamburger) di bawah 992px --}}
<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menu utama">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="bi bi-basket2-fill"></i></span>
            <span>
                <span class="sidebar-brand-title d-block">{{ config('app.name') }}</span>
                <span class="sidebar-brand-sub">Supplier Sayur Hotel &amp; Resto</span>
            </span>
        </a>
        <button type="button" class="btn-close btn-close-white me-3 d-lg-none" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Tutup menu"></button>
    </div>

    <nav class="sidebar-nav">
        @foreach ($menus as $group)
            @if ($group['heading'])
                <div class="sidebar-heading">{{ $group['heading'] }}</div>
            @endif
            @foreach ($group['items'] as $item)
                @php $aktif = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="sidebar-link {{ $aktif ? 'active' : '' }}"
                   @if ($aktif) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div class="sidebar-user">
            <div class="sidebar-user-name text-truncate">{{ $user->name }}</div>
            <div class="sidebar-user-email text-truncate">{{ $user->email }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout" title="Keluar" aria-label="Keluar">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </button>
        </form>
    </div>
</aside>
