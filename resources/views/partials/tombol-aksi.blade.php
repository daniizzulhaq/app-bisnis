{{-- Tombol edit & hapus di baris tabel. Variabel: $edit (url), $hapus (url), $nama --}}
<div class="d-inline-flex gap-1">
    <a href="{{ $edit }}" class="btn btn-sm btn-light" title="Edit" aria-label="Edit {{ $nama }}">
        <i class="bi bi-pencil-square"></i>
    </a>
    <form method="POST" action="{{ $hapus }}" class="form-delete" data-nama="{{ $nama }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus" aria-label="Hapus {{ $nama }}">
            <i class="bi bi-trash3"></i>
        </button>
    </form>
</div>
