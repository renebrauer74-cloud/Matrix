<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('creates an admin with an existing bcrypt hash', function () {
    $hash = Hash::make('altes-passwort');

    $this->artisan('matrix:admin', ['email' => 'Admin@Matrix-Bochum.de', '--name' => 'Admin', '--password-hash' => $hash])
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@matrix-bochum.de')->sole();
    expect(Hash::check('altes-passwort', $user->password))->toBeTrue();
});

it('creates an admin with a password option without prompting', function () {
    $this->artisan('matrix:admin', ['email' => 'admin@matrix-bochum.de', '--name' => 'Admin', '--password' => 'ein-langes-passwort'])
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@matrix-bochum.de')->sole();
    expect(Hash::check('ein-langes-passwort', $user->password))->toBeTrue();
});

it('rejects a password option that is too short', function () {
    $this->artisan('matrix:admin', ['email' => 'admin@matrix-bochum.de', '--password' => 'kurz'])
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects password hashes that are not bcrypt', function () {
    $this->artisan('matrix:admin', ['email' => 'admin@matrix-bochum.de', '--password-hash' => 'klartext'])
        ->assertFailed();
});

it('creates and removes the demo events', function () {
    Storage::fake('uploads');
    Event::factory()->create(['title' => 'Echtes Event']);

    $this->artisan('matrix:demo')->assertSuccessful();
    expect(Event::query()->count())->toBe(11);

    $this->artisan('matrix:demo', ['--remove' => true])->assertSuccessful();
    expect(Event::query()->pluck('title')->all())->toBe(['Echtes Event']);
});
