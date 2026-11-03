{{--
    Filter periode (dari - sampai) dengan pintasan bulan ini / bulan lalu.
    Variabel: $periode (App\Support\Periode)
    Opsional: $kecuali (query yang tidak dibawa), $pilihanNama/$pilihanLabel/$pilihan (dropdown tambahan)
--}}
@php
    $bulanIni = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    $bulanLalu = [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()];
    $tahunIni = [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()];
    $query = request()->except(['dari', 'sampai', 'page', 'export']);
@endphp
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @foreach (request()->except(['dari', 'sampai', 'page', 'export', ...($kecuali ?? []), ...(isset($pilihanNama) ? [$pilihanNama] : [])]) as $k => $v)
                @if (is_string($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="dari">Dari</label>
                <input type="date" id="dari" name="dari" value="{{ $periode->dariStr() }}" class="form-control">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="sampai">Sampai</label>
                <input type="date" id="sampai" name="sampai" value="{{ $periode->sampaiStr() }}" class="form-control">
            </div>
            @isset($pilihanNama)
                <div class="col-md-4 col-xl-3">
                    <label class="form-label" for="f-pilihan">{{ $pilihanLabel }}</label>
                    <select id="f-pilihan" name="{{ $pilihanNama }}" class="form-select tom-select" data-placeholder="Semua {{ strtolower($pilihanLabel) }}">
                        <option value="">Semua {{ strtolower($pilihanLabel) }}</option>
                        @foreach ($pilihan as $opsi)
                            <option value="{{ $opsi->id }}" @selected((string) request($pilihanNama) === (string) $opsi->id)>{{ $opsi->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <div class="col-12 col-md-auto d-flex gap-2 filter-actions">
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
