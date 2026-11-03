@extends('layouts.app')

@section('title', 'Riwayat Hapus')
@section('subtitle', 'Data yang terhapus bisa dipulihkan kembali')

@php $jenisList = \App\Http\Controllers\RiwayatHapusController::JENIS; @endphp

@section('content')
    {{-- Tab per jenis data --}}
    <div class="grup-supplier d-flex flex-wrap gap-2 mb-3">
        @foreach ($jenisList as $kunci => [$label, $ikon])
            <a href="{{ route('riwayat-hapus.index', ['jenis' => $kunci]) }}"
               class="btn btn-sm {{ $jenis === $kunci ? 'btn-primary' : 'btn-light' }}">
                <i class="bi {{ $ikon }}"></i>{{ $label }}
                <span class="badge rounded-pill {{ $jenis === $kunci ? 'bg-white text-dark' : 'text-bg-light border' }} ms-1">{{ $jumlah[$kunci] }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        @if ($data->isEmpty())
            <x-empty-state icon="bi-trash3" title="Tidak ada {{ strtolower($jenisList[$jenis][0]) }} yang terhapus"
                           message="Data yang dihapus akan muncul di sini dan bisa dipulihkan." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Keterangan</th>
                        <th>Dihapus</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($data as $i => $m)
                        @php $r = $baris[$i]; @endphp
                        <tr>
                            <td class="fw-semibold td-wrap">
                                @isset($r['warna'])
                                    <x-supplier :nama="$m->nama" :warna="$r['warna']" class="fw-semibold" />
                                @else
                                    {{ $r['judul'] }}
                                @endisset
                            </td>
                            <td class="small text-secondary td-wrap">{{ $r['ket'] }}</td>
                            <td class="text-nowrap small">{{ tanggal($m->deleted_at, 'd M Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <form method="POST" action="{{ route('riwayat-hapus.pulihkan', [$jenis, $m->id]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success-soft" data-loading="Memulihkan..." type="submit">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('riwayat-hapus.hapus', [$jenis, $m->id]) }}"
                                          class="form-delete" data-nama="{{ $r['judul'] }}" data-permanen="1">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger" title="Hapus permanen">
                                            <i class="bi bi-trash3 me-1"></i>Hapus Permanen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($data->hasPages())
                <div class="card-body border-top">{{ $data->links() }}</div>
            @endif
        @endif
    </div>

    <p class="small text-secondary mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        <b>Pulihkan</b> mengembalikan data seperti semula (pembayaran yang dipulihkan otomatis tercatat lagi di Cashflow dan status nota dihitung ulang).
        <b>Hapus Permanen</b> tidak bisa dibatalkan.
    </p>
@endsection
