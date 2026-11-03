{{--
    Satu baris item nota (model sesuai permintaan klien):
    Supplier | Barang | Qty | Harga Jual | Harga Beli | Satuan | Total Jual | Total Beli | X
    $i = index baris (atau "__INDEX__" untuk template JS), $item = array nilai.
    $grupBarang (dari rekapan.form) = barang dikelompokkan per supplier.
--}}
@php
    $err = fn ($f) => is_numeric($i) ? $errors->first("items.$i.$f") : null;
@endphp
<tr class="item-row">
    <td class="row-no"></td>
    <td class="col-supplier">
        <select name="items[{{ $i }}][supplier_id]" class="form-select sel-supplier tom-warna {{ is_numeric($i) ? 'tom-select' : '' }} @if($err('supplier_id')) is-invalid @endif"
                data-placeholder="Auto dari barang" aria-label="Supplier">
            <option value="">Auto dari barang</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" data-warna="{{ $s->warna }}" @selected((string) ($item['supplier_id'] ?? '') === (string) $s->id)>{{ $s->nama }}</option>
            @endforeach
        </select>
        @if ($err('supplier_id')) <div class="invalid-feedback d-block">{{ $err('supplier_id') }}</div> @endif
    </td>
    <td class="col-barang">
        <select name="items[{{ $i }}][barang_id]" class="form-select sel-barang {{ is_numeric($i) ? 'tom-select' : '' }} @if($err('barang_id')) is-invalid @endif"
                data-placeholder="Cari barang..." aria-label="Barang">
            <option value="">Cari barang...</option>
            @foreach ($grupBarang as $grup)
                <optgroup label="{{ $grup['nama'] }}" data-warna="{{ $grup['warna'] }}">
                    @foreach ($grup['barangs'] as $b)
                        <option value="{{ $b->id }}" @selected((string) ($item['barang_id'] ?? '') === (string) $b->id)>{{ $b->nama }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @if ($err('barang_id')) <div class="invalid-feedback d-block">{{ $err('barang_id') }}</div> @endif
    </td>
    <td class="col-qty">
        <input type="text" name="items[{{ $i }}][qty]" value="{{ input_qty($item['qty'] ?? '') }}" inputmode="decimal"
               class="form-control text-end inp-qty @if($err('qty')) is-invalid @endif" autocomplete="off" aria-label="Qty">
        @if ($err('qty')) <div class="invalid-feedback d-block">{{ $err('qty') }}</div> @endif
    </td>
    <td class="col-harga">
        <input type="text" name="items[{{ $i }}][harga_jual]" value="{{ input_uang($item['harga_jual'] ?? '') }}" inputmode="numeric"
               class="form-control text-end input-uang inp-jual @if($err('harga_jual')) is-invalid @endif" autocomplete="off" aria-label="Harga jual">
        @if ($err('harga_jual')) <div class="invalid-feedback d-block">{{ $err('harga_jual') }}</div> @endif
    </td>
    <td class="col-harga">
        <input type="text" name="items[{{ $i }}][harga_beli]" value="{{ input_uang($item['harga_beli'] ?? '') }}" inputmode="numeric"
               class="form-control text-end input-uang inp-beli @if($err('harga_beli')) is-invalid @endif" autocomplete="off" aria-label="Harga beli">
        @if ($err('harga_beli')) <div class="invalid-feedback d-block">{{ $err('harga_beli') }}</div> @endif
    </td>
    {{-- Kolom tampilan saja (tidak dikirim ke server; server menghitung ulang) --}}
    <td class="col-satuan">
        <input type="text" class="form-control text-center inp-satuan" readonly tabindex="-1" aria-label="Satuan">
    </td>
    <td class="col-total">
        <input type="text" class="form-control text-end inp-total-jual" readonly tabindex="-1" aria-label="Total jual">
    </td>
    <td class="col-total">
        <input type="text" class="form-control text-end inp-total-beli" readonly tabindex="-1" aria-label="Total beli">
    </td>
    <td class="col-aksi">
        <button type="button" class="btn btn-light text-danger btn-hapus-baris" title="Hapus baris" aria-label="Hapus baris">
            <i class="bi bi-x-lg"></i>
        </button>
    </td>
</tr>
