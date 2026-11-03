{{-- Kotak pencarian + tombol tambah. Variabel: $placeholder, $tambahUrl, $tambahLabel, opsional $tersembunyi (query lain yang dibawa) --}}
<div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-3">
    <form method="GET" class="flex-grow-1" style="max-width:420px">
        @foreach (array_filter($tersembunyi ?? [], fn ($v) => $v !== null && $v !== '') as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $placeholder }}">
            @if (request('q'))
                <a href="{{ url()->current() }}" class="btn btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
            @endif
        </div>
    </form>
    <a href="{{ $tambahUrl }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> {{ $tambahLabel }}
    </a>
</div>
