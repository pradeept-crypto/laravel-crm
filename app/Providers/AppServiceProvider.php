<?php

namespace App\Providers;

use App\Mail\BrevoApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || env('APP_ENV') === 'production' || str_contains((string) env('APP_URL', ''), 'https://')) {
            URL::forceScheme('https');
        }

        Mail::extend('brevo', function () {
            return new BrevoApiTransport;
        });
    }
}
