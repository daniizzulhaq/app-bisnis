{{-- Tombol Batal + Simpan di bagian bawah form. Variabel: $batal (url) --}}
<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top form-actions">
    <a href="{{ $batal }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary" data-loading="Menyimpan...">
        <i class="bi bi-check2 me-1"></i> Simpan
    </button>
</div>
