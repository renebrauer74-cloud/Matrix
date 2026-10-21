<?php

use App\Enums\Events\EventCategoryEnum;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Öffentliche Seiten
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('programm/{kategorie?}', [EventController::class, 'index'])
    ->whereIn('kategorie', array_map(fn (EventCategoryEnum $category): string => $category->slug(), EventCategoryEnum::cases()))
    ->name('events.index');
Route::redirect('konzerte', 'programm/konzerte');
Route::redirect('partys', 'programm/partys');
Route::get('event/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('event/{event:slug}/kalender.ics', [EventController::class, 'calendar'])->name('events.calendar');

Route::view('location', 'pages.location')->name('location');
Route::view('anfahrt', 'pages.directions')->name('directions');
Route::view('jobs', 'pages.jobs')->name('jobs');
Route::view('impressum', 'pages.imprint')->name('imprint');
Route::view('datenschutz', 'pages.privacy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Admin-Bereich
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('events', Admin\EventController::class)->except('show');
        Route::post('events/{event}/duplizieren', [Admin\EventController::class, 'duplicate'])->name('events.duplicate');
        Route::post('events/{event}/status', [Admin\EventController::class, 'toggleStatus'])->name('events.status');

        Route::get('benutzer', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('benutzer', [Admin\UserController::class, 'store'])->name('users.store');
        Route::delete('benutzer/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('konto', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('konto', [Admin\AccountController::class, 'update'])->name('account.update');
    });
});
