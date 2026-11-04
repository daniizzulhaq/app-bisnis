<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>Perputaran Modal</span>
        <a href="{{ route('cashflow.create', ['kategori' => 'Modal']) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Input Modal Awal
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach ([
                ['Modal Awal Periode', $modal['saldo_awal']],
                ['Setoran / Penarikan Modal', $modal['tambahan']],
                ['Total Beli Semua Nota', $modal['belanja']],
                ['Modal Kembali', $modal['kembali']],
                ['Modal Tersedia', $modal['saldo_akhir']],
            ] as [$label, $nilai])
                <div class="col-6 col-xl">
                    <div class="small text-secondary mb-1">{{ $label }}</div>
                    <div class="fw-bold {{ $nilai < 0 ? 'text-danger' : '' }}">{{ rupiah($nilai) }}</div>
                </div>
            @endforeach
        </div>
        <p class="small text-secondary mt-3 mb-0">
            Modal berkurang otomatis sebesar total beli semua nota pada tanggal nota.
            Modal kembali sebesar bagian harga beli yang sudah dibayar klien, sesuai proporsi cicilan; laba tidak dihitung sebagai pengembalian modal.
            Kaitkan pembayaran klien ke nota agar pengembalian modal dihitung.
        </p>
    </div>
    @if ($modal['harian']->isNotEmpty())
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="num">Setoran / Penarikan</th>
                    <th class="num">Total Beli Nota</th>
                    <th class="num">Modal Kembali</th>
                    <th class="num">Modal Tersedia</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($modal['harian'] as $row)
                    <tr>
                        <td>{{ tanggal($row->tanggal) }}</td>
                        <td class="num">{{ rupiah($row->tambahan) }}</td>
                        <td class="num text-danger">{{ rupiah($row->belanja) }}</td>
                        <td class="num text-success">{{ rupiah($row->kembali) }}</td>
                        <td class="num fw-bold {{ $row->saldo < 0 ? 'text-danger' : '' }}">{{ rupiah($row->saldo) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
