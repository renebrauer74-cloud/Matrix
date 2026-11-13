<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('lists the admin users', function () {
    User::factory()->create(['name' => 'Kollegin']);

    actingAsAdmin()->get(route('admin.users.index'))->assertOk()->assertSee('Kollegin');
});

it('creates a user with a lowercase email', function () {
    actingAsAdmin()
        ->post(route('admin.users.store'), ['name' => 'Neu', 'email' => 'Neu@Matrix-Bochum.de', 'password' => 'lang-genug-1', 'password_confirmation' => 'lang-genug-1'])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'neu@matrix-bochum.de')->sole();
    expect(Hash::check('lang-genug-1', $user->password))->toBeTrue();
});

it('rejects short passwords and taken emails', function () {
    User::factory()->create(['email' => 'vergeben@matrix-bochum.de']);

    actingAsAdmin()
        ->post(route('admin.users.store'), ['name' => 'X', 'email' => 'vergeben@matrix-bochum.de', 'password' => 'kurz'])
        ->assertSessionHasErrors(['email', 'password']);
});

it('deletes another user', function () {
    $other = User::factory()->create();

    actingAsAdmin()->delete(route('admin.users.destroy', $other))->assertSessionHas('success');

    expect($other->fresh())->toBeNull();
});

it('does not let admins delete themselves', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');

    expect($admin->fresh())->not->toBeNull();
});

it('updates the own profile without touching the password', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.update'), [
        'name' => 'René',
        'email' => 'Rene@Matrix-Bochum.de',
    ])->assertRedirect(route('admin.account.edit'))->assertSessionHas('success', 'Profil gespeichert.');

    $admin->refresh();
    expect($admin->name)->toBe('René')
        ->and($admin->email)->toBe('rene@matrix-bochum.de')
        ->and(Hash::check('altes-passwort', $admin->password))->toBeTrue();
});

it('ignores an autofilled current password when saving the profile', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.update'), [
        'name' => 'René',
        'email' => $admin->email,
        'current_password' => 'veraltet-aus-dem-browser',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');
});

it('changes the own password and confirms it', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.password.update'), [
        'current_password' => 'altes-passwort',
        'password' => 'neues-passwort',
        'password_confirmation' => 'neues-passwort',
    ])->assertRedirect(route('admin.account.edit'))
        ->assertSessionHas('success', 'Dein Passwort wurde geändert.')
        ->assertSessionHas('password_changed', true);

    expect(Hash::check('neues-passwort', $admin->fresh()->password))->toBeTrue();

    $this->get(route('admin.account.edit'))->assertOk()->assertSee('Dein Passwort wurde geändert.');
});

it('lets a newly created user log in and change the password', function () {
    actingAsAdmin()->post(route('admin.users.store'), [
        'name' => 'Kollege',
        'email' => 'kollege@matrix-bochum.de',
        'password' => 'start-passwort',
        'password_confirmation' => 'start-passwort',
    ])->assertSessionHasNoErrors();

    $this->post(route('admin.logout'));
    $this->post(route('admin.login.store'), ['email' => 'kollege@matrix-bochum.de', 'password' => 'start-passwort'])
        ->assertRedirect(route('admin.dashboard'));

    $this->followingRedirects()->put(route('admin.account.password.update'), [
        'current_password' => 'start-passwort',
        'password' => 'eigenes-passwort',
        'password_confirmation' => 'eigenes-passwort',
    ])->assertOk()->assertSee('Dein Passwort wurde geändert.');

    $this->post(route('admin.logout'));
    $this->post(route('admin.login.store'), ['email' => 'kollege@matrix-bochum.de', 'password' => 'eigenes-passwort'])
        ->assertRedirect(route('admin.dashboard'));
});

it('requires the current password to change the password', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.password.update'), [
        'current_password' => 'falsch',
        'password' => 'neues-passwort',
        'password_confirmation' => 'neues-passwort',
    ])->assertSessionHasErrors(['current_password' => 'Aktuelles Passwort ist falsch.']);

    expect(Hash::check('altes-passwort', $admin->fresh()->password))->toBeTrue();
});

it('rejects a mismatched or unchanged new password', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.password.update'), [
        'current_password' => 'altes-passwort',
        'password' => 'neues-passwort',
        'password_confirmation' => 'anderes-passwort',
    ])->assertSessionHasErrors(['password' => 'Die beiden neuen Passwörter stimmen nicht überein.']);

    $this->actingAs($admin)->put(route('admin.account.password.update'), [
        'current_password' => 'altes-passwort',
        'password' => 'altes-passwort',
        'password_confirmation' => 'altes-passwort',
    ])->assertSessionHasErrors('password');
});

it('requires the password to be repeated when creating a user', function () {
    actingAsAdmin()
        ->post(route('admin.users.store'), ['name' => 'X', 'email' => 'x@matrix-bochum.de', 'password' => 'lang-genug-1', 'password_confirmation' => 'vertippt-123'])
        ->assertSessionHasErrors(['password' => 'Die beiden Passwörter stimmen nicht überein.']);

    expect(User::query()->where('email', 'x@matrix-bochum.de')->exists())->toBeFalse();
});

it('renders the account page', function () {
    actingAsAdmin()->get(route('admin.account.edit'))->assertOk()->assertSee('Passwort ändern');
});
