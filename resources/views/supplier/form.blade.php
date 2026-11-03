@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" action="{{ $supplier->exists ? route('supplier.update', $supplier) : route('supplier.store') }}" novalidate>
                @csrf
                @if ($supplier->exists) @method('PUT') @endif

                @include('partials.form-kontak', ['model' => $supplier, 'placeholderNama' => 'Contoh: Pak Darto - Pasar Induk'])
                @include('partials.form-aksi', ['batal' => route('supplier.index')])
            </form>
        </div>
    </div>
@endsection
