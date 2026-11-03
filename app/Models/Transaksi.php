<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    use SoftDeletes; // hapus = pindah ke Riwayat Hapus

    protected $table = 'transaksis';

    public const STATUS = [
        'belum' => 'Belum',
        'sebagian' => 'Sebagian',
        'lunas' => 'Lunas',
    ];

    protected $fillable = [
        'tanggal', 'klien_id', 'total_qty', 'total_beli', 'total_jual',
        'laba', 'status_bayar', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_qty' => 'decimal:2',
            'total_beli' => 'decimal:2',
            'total_jual' => 'decimal:2',
            'laba' => 'decimal:2',
        ];
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class)->withTrashed();
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /** Pembayaran dari klien (pelunasan piutang nota ini). */
    public function pembayaranKlien(): HasMany
    {
        return $this->pembayarans()->where('jenis', 'klien');
    }

    /** Nama nota untuk pesan & tampilan, mis. "Nota Hotel Mawar · 03 Nov 2026". */
    public function getLabelAttribute(): string
    {
        return 'Nota '.($this->klien?->nama ?? '').' · '.tanggal($this->tanggal);
    }

    /** Total yang sudah dibayar klien untuk nota ini. */
    public function totalDibayar(): float
    {
        return (float) $this->pembayaranKlien()->sum('jumlah');
    }

    public function sisaTagihan(): float
    {
        return max(0, (float) $this->total_jual - $this->totalDibayar());
    }

    /**
     * Hitung ulang status bayar berdasarkan total pembayaran klien.
     * belum = 0, sebagian = 0 < bayar < total, lunas = bayar >= total.
     */
    public function refreshStatusBayar(): void
    {
        $dibayar = $this->totalDibayar();
        $total = (float) $this->total_jual;

        $status = match (true) {
            $dibayar <= 0 => 'belum',
            $dibayar + 0.009 >= $total => 'lunas',
            default => 'sebagian',
        };

        if ($this->status_bayar !== $status) {
            $this->forceFill(['status_bayar' => $status])->saveQuietly();
        }
    }

    /**
     * Filter halaman rekapan: periode, klien, supplier, barang, status.
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($f['klien_id'] ?? null, fn ($q, $v) => $q->where('klien_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status_bayar', $v))
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->whereHas('details', fn ($d) => $d->where('supplier_id', $v)))
            ->when($f['barang_id'] ?? null, fn ($q, $v) => $q->whereHas('details', fn ($d) => $d->where('barang_id', $v)));
    }
}
