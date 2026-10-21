<?php

use App\Enums\Events\EventCategoryEnum;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CorporateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LostItemController;
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
Route::view('impressum', 'pages.imprint')->name('imprint');
Route::view('datenschutz', 'pages.privacy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Formulare – Einsendungen landen im Admin-Posteingang
|--------------------------------------------------------------------------
*/

Route::get('kontakt', [ContactController::class, 'show'])->name('contact');
Route::get('fundsachen', [LostItemController::class, 'show'])->name('lost-items');
Route::get('jobs', [JobController::class, 'show'])->name('jobs');
Route::get('firmen', [CorporateController::class, 'show'])->name('corporate');

Route::middleware('throttle:site-forms')->group(function () {
    Route::post('kontakt', [ContactController::class, 'store'])->name('contact.store');
    Route::post('fundsachen', [LostItemController::class, 'store'])->name('lost-items.store');
    Route::post('jobs', [JobController::class, 'store'])->name('jobs.store');
    Route::post('firmen', [CorporateController::class, 'store'])->name('corporate.store');
});

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

        Route::get('nachrichten', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('nachrichten/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
        Route::put('nachrichten/{message}', [Admin\MessageController::class, 'update'])->name('messages.update');
        Route::delete('nachrichten/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('nachrichten/{message}/ungelesen', [Admin\MessageController::class, 'markUnread'])->name('messages.unread');
        Route::get('nachrichten/{message}/anhang/{media}', [Admin\MessageController::class, 'attachment'])->whereNumber('media')->name('messages.attachment');
        Route::post('nachrichten/{message}/antworten', [Admin\MessageReplyController::class, 'store'])->name('messages.replies.store');

        Route::resource('stellen', Admin\JobPositionController::class)
            ->except('show')
            ->parameters(['stellen' => 'jobPosition'])
            ->names('job-positions');
        Route::post('stellen/{jobPosition}/sichtbarkeit', [Admin\JobPositionController::class, 'toggle'])->name('job-positions.toggle');

        Route::get('seiten/{page}', [Admin\PageController::class, 'edit'])->name('pages.edit');
        Route::put('seiten/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
        Route::post('seiten/{page}/medien', [Admin\PageMediaController::class, 'store'])->name('pages.media.store');
        Route::post('seiten/{page}/medien/{media}/verschieben', [Admin\PageMediaController::class, 'move'])->whereNumber('media')->name('pages.media.move');
        Route::delete('seiten/{page}/medien/{media}', [Admin\PageMediaController::class, 'destroy'])->whereNumber('media')->name('pages.media.destroy');

        Route::get('benutzer', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('benutzer', [Admin\UserController::class, 'store'])->name('users.store');
        Route::delete('benutzer/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('konto', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('konto', [Admin\AccountController::class, 'update'])->name('account.update');
    });
});
