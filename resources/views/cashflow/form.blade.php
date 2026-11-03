@extends('layouts.app')

@section('title', $cashflow->exists ? 'Edit Cashflow' : 'Input Cashflow Manual')
@section('subtitle', 'Untuk modal, biaya operasional, transportasi, dll.')

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" novalidate action="{{ $cashflow->exists ? route('cashflow.update', $cashflow) : route('cashflow.store') }}">
                @csrf
                @if ($cashflow->exists) @method('PUT') @endif

                <div class="mb-4">
                    <label class="form-label d-block">Tipe <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tipe" id="tipe-masuk" value="masuk" @checked(old('tipe', $cashflow->tipe) === 'masuk')>
                        <label class="btn btn-outline-success" for="tipe-masuk"><i class="bi bi-arrow-down-left me-1"></i> Uang Masuk</label>
                        <input type="radio" class="btn-check" name="tipe" id="tipe-keluar" value="keluar" @checked(old('tipe', $cashflow->tipe) === 'keluar')>
                        <label class="btn btn-outline-danger" for="tipe-keluar"><i class="bi bi-arrow-up-right me-1"></i> Uang Keluar</label>
                    </div>
                    @error('tipe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $cashflow->tanggal?->toDateString()) }}"
                               class="form-control @error('tanggal') is-invalid @enderror" required>
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                        <input type="text" id="kategori" name="kategori" list="daftar-kategori" maxlength="50"
                               value="{{ old('kategori', $cashflow->kategori) }}" placeholder="Pilih atau ketik"
                               class="form-control @error('kategori') is-invalid @enderror" required>
                        <datalist id="daftar-kategori">
                            @foreach (\App\Models\Cashflow::KATEGORI_MANUAL as $k)
                                <option value="{{ $k }}">
                            @endforeach
                        </datalist>
                        @error('kategori') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="nominal" class="form-label">Nominal <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="nominal" name="nominal" inputmode="numeric" value="{{ input_uang(old('nominal', $cashflow->nominal)) }}"
                                   class="form-control input-uang text-end @error('nominal') is-invalid @enderror" placeholder="0" required>
                            @error('nominal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror"
                                  placeholder="Mis. bensin mobil box pengiriman">{{ old('keterangan', $cashflow->keterangan) }}</textarea>
                        @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @include('partials.form-aksi', ['batal' => route('cashflow.index')])
            </form>
        </div>
    </div>
@endsection
