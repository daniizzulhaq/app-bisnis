@extends('layouts.app')

@section('title', $pembayaran->exists ? 'Edit Pembayaran' : 'Catat Pembayaran')
@section('subtitle', 'Pembayaran otomatis tercatat di Cashflow')

@php
    $jenis = old('jenis', $pembayaran->jenis);
    $dariNota = request()->filled('transaksi') || old('kembali_ke_nota');
@endphp

@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body">
            <form method="POST" novalidate
                  action="{{ $pembayaran->exists ? route('pembayaran.update', $pembayaran) : route('pembayaran.store') }}">
                @csrf
                @if ($pembayaran->exists) @method('PUT') @endif
                @if ($dariNota) <input type="hidden" name="kembali_ke_nota" value="1"> @endif

                <div class="mb-4">
                    <label class="form-label d-block">Jenis Pembayaran <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        @foreach ([
                            'klien' => ['Terima dari Klien', 'Pelunasan piutang · uang masuk', 'bi-arrow-down-left-circle', 'success'],
                            'supplier' => ['Bayar ke Supplier', 'Pelunasan hutang · uang keluar', 'bi-arrow-up-right-circle', 'danger'],
                        ] as $val => [$judul, $ket, $ikon, $warna])
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="jenis" id="jenis-{{ $val }}" value="{{ $val }}" @checked($jenis === $val) autocomplete="off">
                                <label class="btn btn-outline-{{ $warna }} w-100 text-start p-3" for="jenis-{{ $val }}">
                                    <i class="bi {{ $ikon }} fs-4 float-end"></i>
                                    <div class="fw-semibold">{{ $judul }}</div>
                                    <div class="small opacity-75">{{ $ket }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('jenis') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6 grup-klien">
                        <label for="klien_id" class="form-label">Klien <span class="text-danger">*</span></label>
                        <select id="klien_id" name="klien_id" class="form-select tom-select @error('klien_id') is-invalid @enderror" data-placeholder="Pilih klien...">
                            <option value="">Pilih klien...</option>
                            @foreach ($kliens as $k)
                                <option value="{{ $k->id }}" @selected((string) old('klien_id', $pembayaran->klien_id) === (string) $k->id)>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                        @error('klien_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 grup-supplier">
                        <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select id="supplier_id" name="supplier_id" class="form-select tom-select @error('supplier_id') is-invalid @enderror" data-placeholder="Pilih supplier...">
                            <option value="">Pilih supplier...</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected((string) old('supplier_id', $pembayaran->supplier_id) === (string) $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="transaksi_id" class="form-label">Kaitkan ke Nota <span class="text-secondary fw-normal">(opsional)</span></label>
                        <select id="transaksi_id" name="transaksi_id" class="@error('transaksi_id') is-invalid @enderror"
                                data-selected="{{ old('transaksi_id', $pembayaran->transaksi_id) }}">
                            <option value="">Tanpa nota</option>
                        </select>
                        @error('transaksi_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text" id="info-nota">Pilih klien/supplier untuk melihat daftar nota.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $pembayaran->tanggal?->toDateString()) }}"
                               class="form-control @error('tanggal') is-invalid @enderror" required>
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="jumlah" name="jumlah" inputmode="numeric" value="{{ input_uang(old('jumlah', $pembayaran->jumlah)) }}"
                                   class="form-control input-uang text-end @error('jumlah') is-invalid @enderror" placeholder="0" required>
                            @error('jumlah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="metode" class="form-label">Metode <span class="text-danger">*</span></label>
                        <select id="metode" name="metode" class="form-select @error('metode') is-invalid @enderror">
                            @foreach (\App\Models\Pembayaran::METODE as $val => $label)
                                <option value="{{ $val }}" @selected(old('metode', $pembayaran->metode) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('metode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror"
                                  placeholder="Mis. transfer BCA a.n. Hotel ...">{{ old('keterangan', $pembayaran->keterangan) }}</textarea>
                        @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @include('partials.form-aksi', ['batal' => $dariNota && $pembayaran->transaksi_id
                    ? route('rekapan.show', $pembayaran->transaksi_id) : route('pembayaran.index')])
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlNota = @json(route('pembayaran.nota'));
    const elNota = document.getElementById('transaksi_id');
    const elJumlah = document.getElementById('jumlah');
    const info = document.getElementById('info-nota');
    let terpilih = elNota.dataset.selected || '';
    let sisaNota = {};

    const tsNota = new TomSelect(elNota, {
        dropdownParent: 'body',
        allowEmptyOption: true,
        placeholder: 'Tanpa nota',
        render: { no_results: () => '<div class="no-results">Tidak ada nota</div>' },
    });

    const jenis = () => document.querySelector('input[name="jenis"]:checked')?.value;
    const idPihak = () => document.getElementById(jenis() === 'supplier' ? 'supplier_id' : 'klien_id').value;

    /** Tampilkan field klien ATAU supplier sesuai jenis. */
    function aturTampilan() {
        const j = jenis();
        document.querySelectorAll('.grup-klien').forEach((el) => el.classList.toggle('d-none', j !== 'klien'));
        document.querySelectorAll('.grup-supplier').forEach((el) => el.classList.toggle('d-none', j !== 'supplier'));
    }

    /** Muat ulang daftar nota milik klien/supplier terpilih. */
    async function muatNota() {
        tsNota.clear(true);
        tsNota.clearOptions();
        tsNota.addOption({ value: '', text: 'Tanpa nota' });
        sisaNota = {};

        const id = idPihak();
        if (!id) {
            info.textContent = 'Pilih klien/supplier untuk melihat daftar nota.';
            tsNota.setValue('', true);
            return;
        }

        info.textContent = 'Memuat nota...';
        const res = await fetch(`${urlNota}?jenis=${jenis()}&id=${id}&selected=${terpilih}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        data.forEach((n) => { tsNota.addOption(n); sisaNota[n.value] = n.sisa; });
        info.textContent = jenis() === 'klien'
            ? `${data.length} nota belum lunas. Status nota akan diperbarui otomatis.`
            : `${data.length} nota berisi barang dari supplier ini.`;

        tsNota.setValue(data.some((n) => String(n.value) === String(terpilih)) ? terpilih : '', true);
        terpilih = '';
    }

    // Saat nota klien dipilih dan jumlah masih kosong, isi otomatis dengan sisa tagihan
    tsNota.on('change', (val) => {
        if (val && jenis() === 'klien' && !elJumlah.value && sisaNota[val]) {
            elJumlah.value = Math.round(sisaNota[val]).toLocaleString('id-ID');
        }
    });

    document.querySelectorAll('input[name="jenis"]').forEach((r) => r.addEventListener('change', () => { aturTampilan(); muatNota(); }));
    document.getElementById('klien_id').addEventListener('change', muatNota);
    document.getElementById('supplier_id').addEventListener('change', muatNota);

    aturTampilan();
    muatNota();
});
</script>
@endpush
