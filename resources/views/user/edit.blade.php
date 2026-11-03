@extends('layouts.app')

@section('title', 'Pengaturan User')
@section('subtitle', 'Kelola akun admin')

@section('content')
    <div class="row g-3" style="max-width:1100px">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-person me-1"></i> Profil</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.update') }}" novalidate>
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="100"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150"
                                   class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Email ini dipakai untuk login.</div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" data-loading="Menyimpan..."><i class="bi bi-check2 me-1"></i> Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-shield-lock me-1"></i> Ganti Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.password') }}" novalidate>
                        @csrf @method('PUT')
                        @foreach ([
                            'current_password' => ['Password Lama', 'current-password'],
                            'password' => ['Password Baru', 'new-password'],
                            'password_confirmation' => ['Konfirmasi Password Baru', 'new-password'],
                        ] as $field => [$label, $auto])
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                                <input type="password" id="{{ $field }}" name="{{ $field }}" autocomplete="{{ $auto }}"
                                       class="form-control {{ $errors->password->has($field) ? 'is-invalid' : '' }}" required>
                                @if ($errors->password->has($field))
                                    <div class="invalid-feedback">{{ $errors->password->first($field) }}</div>
                                @endif
                            </div>
                        @endforeach
                        <div class="form-text mb-3">Minimal 8 karakter.</div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" data-loading="Menyimpan..."><i class="bi bi-key me-1"></i> Ganti Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
