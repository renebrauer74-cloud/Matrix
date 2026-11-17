<?php

use App\Enums\Events\EventCategoryEnum;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CorporateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\LocationController;
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

Route::get('location', [LocationController::class, 'show'])->name('location');
Route::view('anfahrt', 'pages.directions')->name('directions');
Route::get('impressum', [LegalPageController::class, 'imprint'])->name('imprint');
Route::get('datenschutz', [LegalPageController::class, 'privacy'])->name('privacy');

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

        // Bereits gelöschte Veranstaltung (z. B. über die Zurück-Taste aufgerufen): zur Liste statt 404
        $eventMissing = fn () => to_route('admin.events.index')->with('error', 'Diese Veranstaltung gibt es nicht mehr – sie wurde vermutlich gelöscht.');

        Route::resource('events', Admin\EventController::class)->except('show')->missing($eventMissing);
        Route::post('events/{event}/duplizieren', [Admin\EventController::class, 'duplicate'])->name('events.duplicate')->missing($eventMissing);
        Route::post('events/{event}/status', [Admin\EventController::class, 'toggleStatus'])->name('events.status')->missing($eventMissing);

        Route::get('nachrichten', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('nachrichten/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
        Route::put('nachrichten/{message}', [Admin\MessageController::class, 'update'])->name('messages.update');
        Route::delete('nachrichten/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('nachrichten/{message}/ungelesen', [Admin\MessageController::class, 'markUnread'])->name('messages.unread');
        Route::get('nachrichten/{message}/anhang/{media}', [Admin\MessageController::class, 'attachment'])->whereNumber('media')->name('messages.attachment');
        Route::post('nachrichten/{message}/antworten', [Admin\MessageReplyController::class, 'store'])->name('messages.replies.store');

        Route::resource('veranstalter', Admin\OrganizerController::class)
            ->except('show')
            ->parameters(['veranstalter' => 'organizer'])
            ->names('organizers');

        Route::resource('stellen', Admin\JobPositionController::class)
            ->except('show')
            ->parameters(['stellen' => 'jobPosition'])
            ->names('job-positions');
        Route::post('stellen/{jobPosition}/sichtbarkeit', [Admin\JobPositionController::class, 'toggle'])->name('job-positions.toggle');

        Route::get('location/raeume/{room}', [Admin\RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('location/raeume/{room}', [Admin\RoomController::class, 'update'])->name('rooms.update');

        Route::get('seiten/{page}', [Admin\PageController::class, 'edit'])->name('pages.edit');
        Route::put('seiten/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
        Route::post('seiten/{page}/medien', [Admin\PageMediaController::class, 'store'])->name('pages.media.store');
        Route::post('seiten/{page}/medien/{media}/verschieben', [Admin\PageMediaController::class, 'move'])->whereNumber('media')->name('pages.media.move');
        Route::delete('seiten/{page}/medien/{media}', [Admin\PageMediaController::class, 'destroy'])->whereNumber('media')->name('pages.media.destroy');

        Route::get('benutzer', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('benutzer', [Admin\UserController::class, 'store'])->name('users.store');
        Route::delete('benutzer/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        // Schnittstelle der Quittungs-App – vor der Datei-Route, die sonst alles unter quittung/ abfängt
        Route::prefix('quittung/api')->name('receipts.api.')->group(function () {
            Route::get('stand', Admin\ReceiptSyncController::class)->name('sync');

            Route::post('firmen', [Admin\ReceiptCompanyController::class, 'store'])->name('companies.store');
            Route::put('firmen/{receiptCompany}', [Admin\ReceiptCompanyController::class, 'update'])->name('companies.update');
            Route::delete('firmen/{receiptCompany}', [Admin\ReceiptCompanyController::class, 'destroy'])->name('companies.destroy');

            Route::post('kontakte', [Admin\ReceiptContactController::class, 'store'])->name('contacts.store');
            Route::put('kontakte/{receiptContact}', [Admin\ReceiptContactController::class, 'update'])->name('contacts.update');
            Route::delete('kontakte/{receiptContact}', [Admin\ReceiptContactController::class, 'destroy'])->name('contacts.destroy');
            Route::post('kontakte/{receiptContact}/dateien', [Admin\ReceiptContactFileController::class, 'store'])->name('contacts.files.store');
            Route::get('kontakte/{receiptContact}/dateien/{media}', [Admin\ReceiptContactFileController::class, 'show'])->whereNumber('media')->name('contacts.files.show');
            Route::delete('kontakte/{receiptContact}/dateien/{media}', [Admin\ReceiptContactFileController::class, 'destroy'])->whereNumber('media')->name('contacts.files.destroy');

            Route::post('mitarbeiter', [Admin\ReceiptEmployeeController::class, 'store'])->name('employees.store');
            Route::put('mitarbeiter/{receiptEmployee}', [Admin\ReceiptEmployeeController::class, 'update'])->name('employees.update');
            Route::delete('mitarbeiter/{receiptEmployee}', [Admin\ReceiptEmployeeController::class, 'destroy'])->name('employees.destroy');

            Route::post('quittungen', [Admin\ReceiptController::class, 'store'])->name('receipts.store');
            Route::put('quittungen/{receipt}', [Admin\ReceiptController::class, 'update'])->name('receipts.update');
            Route::delete('quittungen/{receipt}', [Admin\ReceiptController::class, 'destroy'])->name('receipts.destroy');
            Route::post('quittungen/{receipt}/pdf', [Admin\ReceiptPdfController::class, 'store'])->name('receipts.pdf.store');
            Route::get('quittungen/{receipt}/pdf', [Admin\ReceiptPdfController::class, 'show'])->name('receipts.pdf.show');
            Route::get('quittungen/{receipt}/unterschrift', [Admin\ReceiptPdfController::class, 'signature'])->name('receipts.signature');
            Route::post('quittungen/{receipt}/gebucht', Admin\ReceiptBookingController::class)->name('receipts.booking');
            Route::post('quittungen/{receipt}/storno', Admin\ReceiptCancellationController::class)->name('receipts.cancel');

            Route::post('umzug/firmen', [Admin\ReceiptImportController::class, 'companies'])->name('import.companies');
            Route::post('umzug/quittungen', [Admin\ReceiptImportController::class, 'receipt'])->name('import.receipts');
        });

        Route::get('quittung', [Admin\ReceiptAppController::class, 'show'])->name('receipts.show');
        Route::get('quittung/{path}', [Admin\ReceiptAppController::class, 'file'])->where('path', '.*')->name('receipts.file');

        Route::get('konto', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('konto', [Admin\AccountController::class, 'update'])->name('account.update');
        Route::put('konto/passwort', [Admin\AccountPasswordController::class, 'update'])->name('account.password.update');
    });
});
