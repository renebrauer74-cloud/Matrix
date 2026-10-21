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
