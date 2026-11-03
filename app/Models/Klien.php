<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Klien extends Model
{
    use SoftDeletes; // hapus = pindah ke Riwayat Hapus

    protected $table = 'kliens';

    protected $fillable = ['nama', 'no_hp', 'alamat', 'keterangan'];

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
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
        $nota = $this->transaksis()->count();
        $bayar = $this->pembayarans()->count();

        if ($nota || $bayar) {
            return "Klien \"{$this->nama}\" tidak dapat dihapus karena sudah memiliki {$nota} nota dan {$bayar} pembayaran.";
        }

        return null;
    }
}
