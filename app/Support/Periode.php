<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Filter periode (dari - sampai) yang dipakai dashboard, cashflow, dan laporan.
 * Default: awal s/d akhir bulan berjalan.
 */
class Periode
{
    public function __construct(public Carbon $dari, public Carbon $sampai)
    {
    }

    public static function dariRequest(Request $request): self
    {
        $dari = self::parse($request->input('dari')) ?? now()->startOfMonth();
        $sampai = self::parse($request->input('sampai')) ?? now()->endOfMonth();

        // Tukar otomatis bila tanggal terbalik
        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return new self($dari->startOfDay(), $sampai->endOfDay());
    }

    private static function parse(?string $nilai): ?Carbon
    {
        if (blank($nilai)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $nilai);
        } catch (\Throwable) {
            return null;
        }
    }

    public function dariStr(): string
    {
        return $this->dari->toDateString();
    }

    public function sampaiStr(): string
    {
        return $this->sampai->toDateString();
    }

    /** Contoh: "01 Nov 2026 – 30 Nov 2026" */
    public function label(): string
    {
        return tanggal($this->dari).' – '.tanggal($this->sampai);
    }
}
