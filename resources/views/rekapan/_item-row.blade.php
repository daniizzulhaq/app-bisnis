{{-- Satu baris item nota. $i = index baris (atau "__INDEX__" untuk template JS), $item = array nilai --}}
@php
    $err = fn ($f) => is_numeric($i) ? $errors->first("items.$i.$f") : null;
@endphp
<tr class="item-row">
    <td class="text-secondary row-no align-top pt-3"></td>
    <td class="col-barang">
        <select name="items[{{ $i }}][barang_id]" class="form-select sel-barang {{ is_numeric($i) ? 'tom-select' : '' }} @if($err('barang_id')) is-invalid @endif"
                data-placeholder="Pilih barang...">
            <option value="">Pilih barang...</option>
            @foreach ($barangs as $b)
                <option value="{{ $b->id }}" @selected((string) ($item['barang_id'] ?? '') === (string) $b->id)>{{ $b->nama }} ({{ $b->satuan }})</option>
            @endforeach
        </select>
        @if ($err('barang_id')) <div class="invalid-feedback d-block">{{ $err('barang_id') }}</div> @endif
    </td>
    <td class="col-supplier">
        <select name="items[{{ $i }}][supplier_id]" class="form-select sel-supplier {{ is_numeric($i) ? 'tom-select' : '' }} @if($err('supplier_id')) is-invalid @endif"
                data-placeholder="Pilih supplier...">
            <option value="">Pilih supplier...</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" @selected((string) ($item['supplier_id'] ?? '') === (string) $s->id)>{{ $s->nama }}</option>
            @endforeach
        </select>
        @if ($err('supplier_id')) <div class="invalid-feedback d-block">{{ $err('supplier_id') }}</div> @endif
    </td>
    <td class="col-qty">
        <input type="text" name="items[{{ $i }}][qty]" value="{{ input_qty($item['qty'] ?? '') }}" inputmode="decimal"
               class="form-control text-end inp-qty @if($err('qty')) is-invalid @endif" placeholder="0" autocomplete="off">
        <div class="small text-secondary text-end lbl-satuan mt-1"></div>
        @if ($err('qty')) <div class="invalid-feedback d-block">{{ $err('qty') }}</div> @endif
    </td>
    <td class="col-harga">
        <input type="text" name="items[{{ $i }}][harga_beli]" value="{{ input_uang($item['harga_beli'] ?? '') }}" inputmode="numeric"
               class="form-control text-end input-uang inp-beli @if($err('harga_beli')) is-invalid @endif" placeholder="0" autocomplete="off">
        @if ($err('harga_beli')) <div class="invalid-feedback d-block">{{ $err('harga_beli') }}</div> @endif
    </td>
    <td class="col-harga">
        <input type="text" name="items[{{ $i }}][harga_jual]" value="{{ input_uang($item['harga_jual'] ?? '') }}" inputmode="numeric"
               class="form-control text-end input-uang inp-jual @if($err('harga_jual')) is-invalid @endif" placeholder="0" autocomplete="off">
        @if ($err('harga_jual')) <div class="invalid-feedback d-block">{{ $err('harga_jual') }}</div> @endif
    </td>
    <td class="num align-top pt-2 col-subtotal">
        <div>
            <div class="sub-jual fw-bold">Rp 0</div>
            <div class="small text-secondary">beli <span class="sub-beli">Rp 0</span></div>
        </div>
    </td>
    <td class="text-end align-top pt-2 col-aksi">
        <button type="button" class="btn btn-sm btn-light text-danger btn-hapus-baris" title="Hapus baris" aria-label="Hapus baris">
            <i class="bi bi-x-lg"></i>
        </button>
    </td>
</tr>
