<?php

use App\Models\User;

it('redirects guests from the admin area to the login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('logs an admin in with email and password', function () {
    $user = User::factory()->create(['email' => 'admin@matrix-bochum.de', 'password' => 'geheim-geheim']);

    $this->post(route('admin.login.store'), ['email' => 'Admin@Matrix-Bochum.de', 'password' => 'geheim-geheim'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password', function () {
    User::factory()->create(['email' => 'admin@matrix-bochum.de']);

    $this->post(route('admin.login.store'), ['email' => 'admin@matrix-bochum.de', 'password' => 'falsch'])
        ->assertSessionHasErrors(['email' => 'E-Mail oder Passwort ist falsch.']);

    $this->assertGuest();
});

it('locks the login after five failed attempts', function () {
    User::factory()->create(['email' => 'admin@matrix-bochum.de', 'password' => 'geheim-geheim']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), ['email' => 'admin@matrix-bochum.de', 'password' => 'falsch']);
    }

    $this->post(route('admin.login.store'), ['email' => 'admin@matrix-bochum.de', 'password' => 'geheim-geheim'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out', function () {
    actingAsAdmin()
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

it('renders the dashboard', function () {
    actingAsAdmin()->get(route('admin.dashboard'))->assertOk()->assertSee('Als Nächstes');
});
