<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Nama hari/bulan dalam Bahasa Indonesia (Senin, Nov, Des, ...)
        Carbon::setLocale('id');

        // Pagination memakai markup Bootstrap 5
        Paginator::useBootstrapFive();

        // Paksa semua URL memakai https bila FORCE_HTTPS=true
        // (cadangan jika proxy tidak mengirim header X-Forwarded-Proto).
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
