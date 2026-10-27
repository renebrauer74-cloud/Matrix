<?php

use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // In Tests ist die CSRF-Prüfung abgeschaltet – ein abgelaufenes Formular-Token wird hier nachgestellt.
    Route::middleware('web')->post('admin/abgelaufen', fn () => throw new TokenMismatchException);
    Route::middleware('web')->post('formular-abgelaufen', fn () => throw new TokenMismatchException);
});

it('sends to the login with a hint when the session expired while submitting a form', function () {
    $user = User::factory()->create(['password' => 'geheim-geheim']);

    $this->from(route('admin.events.index'))
        ->post('admin/abgelaufen')
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('error', 'Deine Sitzung war abgelaufen – bitte erneut anmelden. Die letzte Aktion wurde nicht ausgeführt.');

    $this->get(route('admin.login'))->assertSee('Deine Sitzung war abgelaufen');

    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'geheim-geheim'])
        ->assertRedirect(route('admin.events.index'));
});

it('returns to the form with the input when only the form token is outdated', function () {
    actingAsAdmin()
        ->from(route('admin.events.create'))
        ->post('admin/abgelaufen', ['title' => 'Nicht verloren', 'password' => 'geheim'])
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHas('error', 'Das Formular war zu lange geöffnet – bitte noch einmal absenden.')
        ->assertSessionHasInput('title', 'Nicht verloren')
        ->assertSessionMissing('_old_input.password');
});

it('keeps the expired page for forms on the website', function () {
    $this->post('formular-abgelaufen')->assertStatus(419);
});

it('leads to the event list when an event no longer exists', function (string $method, string $route) {
    actingAsAdmin()
        ->{$method}(route($route, 999))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('error', 'Diese Veranstaltung gibt es nicht mehr – sie wurde vermutlich gelöscht.');
})->with([
    'edit' => ['get', 'admin.events.edit'],
    'delete' => ['delete', 'admin.events.destroy'],
    'publish' => ['post', 'admin.events.status'],
]);
