<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('lists the admin users', function () {
    User::factory()->create(['name' => 'Kollegin']);

    actingAsAdmin()->get(route('admin.users.index'))->assertOk()->assertSee('Kollegin');
});

it('creates a user with a lowercase email', function () {
    actingAsAdmin()
        ->post(route('admin.users.store'), ['name' => 'Neu', 'email' => 'Neu@Matrix-Bochum.de', 'password' => 'lang-genug-1'])
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

it('updates the own account and password', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.update'), [
        'name' => 'René',
        'email' => 'rene@matrix-bochum.de',
        'current_password' => 'altes-passwort',
        'new_password' => 'neues-passwort',
        'new_password_confirmation' => 'neues-passwort',
    ])->assertRedirect(route('admin.account.edit'))->assertSessionHas('success');

    $admin->refresh();
    expect($admin->name)->toBe('René')
        ->and($admin->email)->toBe('rene@matrix-bochum.de')
        ->and(Hash::check('neues-passwort', $admin->password))->toBeTrue();
});

it('requires the current password to change the password', function () {
    $admin = User::factory()->create(['password' => 'altes-passwort']);

    $this->actingAs($admin)->put(route('admin.account.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'falsch',
        'new_password' => 'neues-passwort',
        'new_password_confirmation' => 'neues-passwort',
    ])->assertSessionHasErrors(['current_password' => 'Aktuelles Passwort ist falsch.']);
});

it('renders the account page', function () {
    actingAsAdmin()->get(route('admin.account.edit'))->assertOk()->assertSee('Passwort ändern');
});
