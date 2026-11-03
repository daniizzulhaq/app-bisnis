<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = ['nama', 'no_hp', 'alamat', 'keterangan'];

    public function transaksiDetails(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        return $query->when($kata, fn ($q) => $q->where(fn ($w) => $w
            ->where('nama', 'like', "%{$kata}%")
            ->orWhere('no_hp', 'like', "%{$kata}%")
            ->orWhere('alamat', 'like', "%{$kata}%")));
    }

    /**
     * Alasan data tidak boleh dihapus (null = boleh dihapus).
     */
    public function alasanTidakBisaDihapus(): ?string
    {
        $item = $this->transaksiDetails()->count();
        $bayar = $this->pembayarans()->count();

        if ($item || $bayar) {
            return "Supplier \"{$this->nama}\" tidak dapat dihapus karena sudah dipakai di {$item} item transaksi dan {$bayar} pembayaran.";
        }

        return null;
    }
}
