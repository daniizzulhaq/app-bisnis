<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashflow extends Model
{
    protected $table = 'cashflows';

    public const KATEGORI_KLIEN = 'Pembayaran Klien';

    public const KATEGORI_SUPPLIER = 'Pembayaran Supplier';

    /** Saran kategori untuk input manual. */
    public const KATEGORI_MANUAL = [
        'Modal', 'Biaya Operasional', 'Transportasi', 'Gaji', 'Sewa', 'Listrik & Air', 'Kemasan', 'Prive', 'Lain-lain',
    ];

    protected $fillable = ['tanggal', 'tipe', 'kategori', 'nominal', 'pembayaran_id', 'keterangan'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    /** Baris otomatis dari pembayaran tidak boleh diubah dari menu Cashflow. */
    public function isOtomatis(): bool
    {
        return $this->pembayaran_id !== null;
    }

    /** Saldo (masuk - keluar) sebelum tanggal tertentu. */
    public static function saldoSebelum(string $tanggal): float
    {
        return (float) static::whereDate('tanggal', '<', $tanggal)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE -nominal END), 0) AS saldo")
            ->value('saldo');
    }
}
