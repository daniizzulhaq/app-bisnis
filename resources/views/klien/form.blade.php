@extends('layouts.app')

@section('title', $klien->exists ? 'Edit Klien' : 'Tambah Klien')

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" action="{{ $klien->exists ? route('klien.update', $klien) : route('klien.store') }}" novalidate>
                @csrf
                @if ($klien->exists) @method('PUT') @endif

                @include('partials.form-kontak', ['model' => $klien, 'placeholderNama' => 'Contoh: Hotel Grand Asri'])
                @include('partials.form-aksi', ['batal' => route('klien.index')])
            </form>
        </div>
    </div>
@endsection
