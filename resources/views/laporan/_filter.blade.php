{{--
    Filter laporan: periode, klien & supplier (boleh lebih dari satu), barang (khusus laporan barang),
    format qty, dan mode harga.
--}}
@php
    $bulanIni = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    $bulanLalu = [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()];
    $tahunIni = [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()];
    $query = request()->except(['dari', 'sampai', 'page', 'export', 'unduh']);
    $jumlahFilter = count($opsi->klienIds) + count($opsi->supplierIds) + count($opsi->barangIds);
@endphp

<div class="d-flex d-md-none gap-2 mb-3">
    <button class="btn btn-light flex-fill" type="button" data-bs-toggle="collapse" data-bs-target="#filterLaporan" aria-expanded="false" aria-controls="filterLaporan">
        <i class="bi bi-funnel me-1"></i> Filter &amp; Opsi
        @if ($jumlahFilter) <span class="badge rounded-pill text-bg-primary ms-1">{{ $jumlahFilter }}</span> @endif
    </button>
</div>

<div class="card mb-3 collapse d-md-block" id="filterLaporan">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="dari">Dari</label>
                <input type="date" id="dari" name="dari" value="{{ $periode->dariStr() }}" class="form-control">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="sampai">Sampai</label>
                <input type="date" id="sampai" name="sampai" value="{{ $periode->sampaiStr() }}" class="form-control">
            </div>
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="f-klien">Klien <span class="text-secondary fw-normal small">· bisa pilih beberapa</span></label>
                <select id="f-klien" name="klien_id[]" multiple class="form-select tom-select" data-placeholder="Semua klien">
                    @foreach ($kliens as $k)
                        <option value="{{ $k->id }}" @selected(in_array($k->id, $opsi->klienIds))>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="f-supplier">Supplier <span class="text-secondary fw-normal small">· bisa pilih beberapa</span></label>
                <select id="f-supplier" name="supplier_id[]" multiple class="form-select tom-select" data-placeholder="Semua supplier">
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" data-warna="{{ $s->warna }}" @selected(in_array($s->id, $opsi->supplierIds))>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            @if ($jenis === 'barang')
                <div class="col-md-6 col-xl-4">
                    <label class="form-label" for="f-barang">Barang <span class="text-secondary fw-normal small">· bisa pilih beberapa</span></label>
                    <select id="f-barang" name="barang_id[]" multiple class="form-select tom-select" data-placeholder="Semua barang">
                        @foreach ($barangs as $b)
                            <option value="{{ $b->id }}" @selected(in_array($b->id, $opsi->barangIds))>{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="f-qty">Format Qty</label>
                <select id="f-qty" name="qty" class="form-select" title="Pakai satuan: 12,5 kg · Angka saja: 12,5 · 2 desimal: 12,50 kg">
                    @foreach (\App\Support\OpsiLaporan::FORMAT_QTY as $val => $label)
                        <option value="{{ $val }}" @selected($opsi->formatQty === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="f-harga">Mode Harga</label>
                <select id="f-harga" name="harga" class="form-select" title="Lengkap: beli, jual & laba · Jual saja: untuk dikirim ke klien · Beli saja: untuk supplier">
                    @foreach (\App\Support\OpsiLaporan::MODE_HARGA as $val => $label)
                        <option value="{{ $val }}" @selected($opsi->modeHarga === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2 filter-actions">
                <a href="{{ url()->current() }}" class="btn btn-light">Reset</a>
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
            </div>
            <div class="col-12 col-xl d-flex flex-wrap gap-1 justify-content-xl-end filter-actions">
                @foreach (['Bulan ini' => $bulanIni, 'Bulan lalu' => $bulanLalu, 'Tahun ini' => $tahunIni] as $label => [$d, $s])
                    <a href="{{ url()->current().'?'.http_build_query($query + ['dari' => $d, 'sampai' => $s]) }}"
                       class="btn btn-sm {{ $periode->dariStr() === $d && $periode->sampaiStr() === $s ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>
    </div>
</div>
