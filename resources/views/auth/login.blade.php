@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="sidebar-brand-icon mx-auto mb-3 text-white" style="width:56px;height:56px;font-size:1.6rem;border-radius:16px;">
                <i class="bi bi-basket2-fill"></i>
            </span>
            <h1 class="h4 fw-bold mb-1">{{ config('app.name') }}</h1>
            <p class="text-secondary small mb-0">Pembukuan supplier sayur untuk hotel &amp; resto</p>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-1">Selamat datang kembali</h2>
                <p class="text-secondary small mb-4">Silakan masuk untuk melanjutkan.</p>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="admin@admin.com" required autofocus autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Masukkan password" required autocomplete="current-password">
                            <button class="btn btn-light" type="button" id="togglePassword" aria-label="Tampilkan password">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label small" for="remember">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
@endsection

@push('scripts')
<script>
    // Tampilkan / sembunyikan password
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
@endpush
