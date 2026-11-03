<?php

namespace App\Models;

use App\Observers\PembayaranObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(PembayaranObserver::class)]
class Pembayaran extends Model
{
    use SoftDeletes; // hapus = pindah ke Riwayat Hapus

    protected $table = 'pembayarans';

    public const JENIS = ['klien' => 'Dari Klien (Masuk)', 'supplier' => 'Ke Supplier (Keluar)'];

    public const METODE = ['tunai' => 'Tunai', 'transfer' => 'Transfer'];

    protected $fillable = [
        'tanggal', 'jenis', 'klien_id', 'supplier_id', 'transaksi_id', 'jumlah', 'metode', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class)->withTrashed();
    }

    public function cashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class);
    }

    /** Nama pihak terkait (klien atau supplier). */
    public function getNamaPihakAttribute(): string
    {
        return $this->jenis === 'klien'
            ? ($this->klien?->nama ?? '-')
            : ($this->supplier?->nama ?? '-');
    }
}
