<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes; // hapus = pindah ke Riwayat Hapus

    protected $table = 'suppliers';

    /** Pilihan warna penanda supplier (tetap boleh pilih warna lain). */
    public const WARNA = [
        '#4f6ef7', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#ec4899', '#14b8a6', '#f97316', '#64748b',
    ];

    protected $fillable = ['nama', 'no_hp', 'alamat', 'keterangan', 'warna'];

    /** Warna preset pertama yang belum dipakai supplier lain (untuk form tambah). */
    public static function warnaBerikut(): string
    {
        $terpakai = static::pluck('warna')->map(fn ($w) => strtolower((string) $w))->all();

        return collect(self::WARNA)->first(fn ($w) => ! in_array($w, $terpakai, true))
            ?? self::WARNA[static::count() % count(self::WARNA)];
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class);
    }

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
            ->where($this->getTable().'.nama', 'like', "%{$kata}%")
            ->orWhere($this->getTable().'.no_hp', 'like', "%{$kata}%")
            ->orWhere($this->getTable().'.alamat', 'like', "%{$kata}%")));
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
