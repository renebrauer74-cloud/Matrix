<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        // Deutsche Admin-URLs wie bisher: /admin/events/neu und /admin/events/{id}/bearbeiten
        Route::resourceVerbs(['create' => 'neu', 'edit' => 'bearbeiten']);

        // Kontakt, Fundsachen, Bewerbung, Firmenanfrage: gegen Formular-Spam pro IP begrenzt
        RateLimiter::for('site-forms', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);
    }
}
