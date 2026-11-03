<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="app-wrapper">
    @include('partials.sidebar')

    <div class="app-main">
        {{-- Header konten: judul di kiri, tanggal hari ini di kanan --}}
        <header class="app-header">
            <div class="d-flex align-items-center gap-3" style="min-width:0">
                <button class="btn-hamburger d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>
                <div style="min-width:0">
                    <h1 class="text-truncate">@yield('title', 'Dashboard')</h1>
                    @hasSection('subtitle')
                        <div class="subtitle">@yield('subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="header-date d-none d-sm-inline-flex">
                <i class="bi bi-calendar3 text-primary"></i>
                {{ tanggal(now(), 'l, d M Y') }}
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

{{-- Notifikasi toast dari session flash --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            RB.toast.fire({ icon: 'success', title: @json(session('success')) });
        @elseif (session('error'))
            RB.toast.fire({ icon: 'error', title: @json(session('error')), timer: 7000 });
        @elseif ($errors->any() || $errors->getBag('password')->any())
            RB.toast.fire({ icon: 'error', title: 'Periksa kembali isian form.' });
        @endif
    });
</script>
@stack('scripts')
</body>
</html>
