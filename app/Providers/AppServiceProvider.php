<?php

namespace App\Providers;

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
    }
}
