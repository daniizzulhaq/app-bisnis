<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use SoftDeletes; // hapus = pindah ke Riwayat Hapus

    protected $table = 'barangs';

    /** Saran satuan untuk form (tetap boleh diketik bebas). */
    public const SATUAN = ['kg', 'ons', 'gram', 'ikat', 'pcs', 'pack', 'sisir', 'karung', 'peti', 'liter'];

    protected $fillable = ['supplier_id', 'nama', 'satuan', 'harga_beli_default', 'harga_jual_default'];

    protected function casts(): array
    {
        return [
            'harga_beli_default' => 'decimal:2',
            'harga_jual_default' => 'decimal:2',
        ];
    }

    /** Supplier langganan (klasifikasi barang). Boleh kosong. */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transaksiDetails(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        return $query->when($kata, fn ($q) => $q->where(fn ($w) => $w
            ->where('nama', 'like', "%{$kata}%")
            ->orWhere('satuan', 'like', "%{$kata}%")));
    }

    /** Margin default per satuan. */
    public function getMarginDefaultAttribute(): float
    {
        return (float) $this->harga_jual_default - (float) $this->harga_beli_default;
    }

    /**
     * Alasan data tidak boleh dihapus (null = boleh dihapus).
     */
    public function alasanTidakBisaDihapus(): ?string
    {
        $item = $this->transaksiDetails()->count();

        return $item
            ? "Barang \"{$this->nama}\" tidak dapat dihapus karena sudah dipakai di {$item} item transaksi."
            : null;
    }
}
