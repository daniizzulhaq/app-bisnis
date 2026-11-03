<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

/** Klien memakai aturan yang sama dengan Supplier, tanpa warna. */
class KlienRequest extends SupplierRequest
{
    public function rules(): array
    {
        return Arr::except(parent::rules(), 'warna');
    }
}
