{{-- Field form yang sama untuk Supplier & Klien. Variabel: $model, $placeholderNama --}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" id="nama" name="nama" value="{{ old('nama', $model->nama) }}"
               class="form-control @error('nama') is-invalid @enderror" placeholder="{{ $placeholderNama }}" required autofocus maxlength="100">
        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="no_hp" class="form-label">No. HP / WhatsApp</label>
        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $model->no_hp) }}" inputmode="tel"
               class="form-control @error('no_hp') is-invalid @enderror" placeholder="0812 3456 7890" maxlength="20">
        @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea id="alamat" name="alamat" rows="2" class="form-control @error('alamat') is-invalid @enderror"
                  placeholder="Alamat lengkap">{{ old('alamat', $model->alamat) }}</textarea>
        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="keterangan" class="form-label">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror"
                  placeholder="Catatan tambahan (opsional)">{{ old('keterangan', $model->keterangan) }}</textarea>
        @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
