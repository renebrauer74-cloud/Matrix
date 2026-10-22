<?php

use App\Models\Event;
use App\Models\Organizer;

it('lists organizers alphabetically with their number of events', function () {
    $zeche = Organizer::factory()->create(['name' => 'Zeche Events', 'city' => 'Essen']);
    Organizer::factory()->create(['name' => 'Ruhrpott Concerts']);
    Event::factory()->count(2)->for($zeche)->create();

    actingAsAdmin()->get(route('admin.organizers.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ruhrpott Concerts', 'Zeche Events'])
        ->assertSee('2 Veranstaltungen')
        ->assertSee('0 Veranstaltungen');
});

it('shows an empty state without organizers', function () {
    actingAsAdmin()->get(route('admin.organizers.index'))
        ->assertOk()
        ->assertSee('Noch keine externen Veranstalter');
});

it('creates an organizer', function () {
    actingAsAdmin()
        ->post(route('admin.organizers.store'), ['name' => 'Ruhrpott Concerts', 'city' => 'Bochum', 'website' => 'ruhrpott.example'])
        ->assertRedirect(route('admin.organizers.index'))
        ->assertSessionHas('success');

    expect(Organizer::query()->sole())
        ->name->toBe('Ruhrpott Concerts')
        ->city->toBe('Bochum')
        ->website->toBe('https://ruhrpott.example');
});

it('validates the organizer data', function () {
    actingAsAdmin()
        ->post(route('admin.organizers.store'), ['name' => '', 'email' => 'keine-mail'])
        ->assertSessionHasErrors([
            'name' => 'Bitte den Namen des Veranstalters eingeben.',
            'email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
        ]);

    expect(Organizer::query()->count())->toBe(0);
});

it('shows the organizer with its events and updates it', function () {
    $organizer = Organizer::factory()->create(['name' => 'Alter Name']);
    $event = Event::factory()->for($organizer)->create(['title' => 'Punk im Pott']);

    actingAsAdmin()->get(route('admin.organizers.edit', $organizer))
        ->assertOk()
        ->assertSee('value="Alter Name"', false)
        ->assertSee('Punk im Pott')
        ->assertSee(route('admin.events.edit', $event), false);

    actingAsAdmin()
        ->put(route('admin.organizers.update', $organizer), ['name' => 'Neuer Name', 'zip' => '44892'])
        ->assertRedirect(route('admin.organizers.index'));

    expect($organizer->refresh())
        ->name->toBe('Neuer Name')
        ->zip->toBe('44892');
});

it('deletes an organizer and keeps its events as matrix events', function () {
    $organizer = Organizer::factory()->create(['name' => 'Ruhrpott Concerts']);
    $event = Event::factory()->for($organizer)->create();

    actingAsAdmin()
        ->delete(route('admin.organizers.destroy', $organizer))
        ->assertRedirect(route('admin.organizers.index'))
        ->assertSessionHas('success', '„Ruhrpott Concerts“ wurde gelöscht. 1 Veranstaltung läuft jetzt als Matrix-Veranstaltung.');

    expect(Organizer::query()->count())->toBe(0)
        ->and($event->refresh()->organizer_id)->toBeNull();
});

it('keeps guests out of the organizer administration', function () {
    $organizer = Organizer::factory()->create();

    $this->get(route('admin.organizers.index'))->assertRedirect(route('admin.login'));
    $this->delete(route('admin.organizers.destroy', $organizer))->assertRedirect(route('admin.login'));

    expect(Organizer::query()->count())->toBe(1);
});
