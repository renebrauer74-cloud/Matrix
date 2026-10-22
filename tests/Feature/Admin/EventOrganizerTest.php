<?php

use App\Models\Event;
use App\Models\Organizer;

/**
 * Formularfelder für einen externen Veranstalter.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function externalOrganizerData(array $overrides = []): array
{
    return eventFormData(array_merge([
        'organizer' => 'external',
        'organizer_id' => '',
        'organizer_name' => 'Ruhrpott Concerts GmbH',
        'organizer_contact_person' => 'Kim Beispiel',
        'organizer_street' => 'Musterweg 1',
        'organizer_zip' => '44787',
        'organizer_city' => 'Bochum',
        'organizer_phone' => '0234 123456',
        'organizer_email' => 'info@ruhrpott-concerts.example',
        'organizer_website' => 'ruhrpott-concerts.example',
    ], $overrides));
}

it('saves the matrix as organizer by default', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData())->assertSessionHasNoErrors();

    expect(Event::query()->sole()->organizer_id)->toBeNull()
        ->and(Organizer::query()->count())->toBe(0);
});

it('creates a new external organizer with master data', function () {
    actingAsAdmin()->post(route('admin.events.store'), externalOrganizerData())->assertSessionHasNoErrors();

    expect(Event::query()->sole()->organizer)
        ->name->toBe('Ruhrpott Concerts GmbH')
        ->contact_person->toBe('Kim Beispiel')
        ->street->toBe('Musterweg 1')
        ->zip->toBe('44787')
        ->city->toBe('Bochum')
        ->email->toBe('info@ruhrpott-concerts.example')
        ->website->toBe('https://ruhrpott-concerts.example');
});

it('reuses a stored organizer and updates its master data', function () {
    $organizer = Organizer::factory()->create(['name' => 'Alter Name']);

    actingAsAdmin()->post(route('admin.events.store'), externalOrganizerData([
        'organizer_id' => (string) $organizer->getKey(),
        'organizer_name' => 'Neuer Name',
    ]))->assertSessionHasNoErrors();

    expect(Organizer::query()->count())->toBe(1)
        ->and($organizer->refresh()->name)->toBe('Neuer Name')
        ->and(Event::query()->sole()->organizer_id)->toBe($organizer->getKey());
});

it('requires the name of an external organizer', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), externalOrganizerData(['organizer_name' => '', 'organizer_email' => 'keine-mail']))
        ->assertSessionHasErrors([
            'organizer_name' => 'Bitte den Namen des Veranstalters eingeben.',
            'organizer_email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
        ]);

    expect(Event::query()->count())->toBe(0);
});

it('ignores the hidden organizer fields when the matrix organizes', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), externalOrganizerData(['organizer' => 'matrix', 'organizer_email' => 'keine-mail']))
        ->assertSessionHasNoErrors();

    expect(Event::query()->sole()->organizer_id)->toBeNull()
        ->and(Organizer::query()->count())->toBe(0);
});

it('switches an event back to the matrix and keeps the organizer master data', function () {
    $event = Event::factory()->external()->create();

    actingAsAdmin()->put(route('admin.events.update', $event), eventFormData())->assertSessionHasNoErrors();

    expect($event->refresh()->organizer_id)->toBeNull()
        ->and(Organizer::query()->count())->toBe(1);
});

it('shows the organizer in the edit form and offers stored organizers', function () {
    $other = Organizer::factory()->create(['name' => 'Zeche Events', 'city' => 'Essen']);
    $event = Event::factory()->for(Organizer::factory()->state(['name' => 'Ruhrpott Concerts GmbH']))->create();

    actingAsAdmin()->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('value="external" checked', false)
        ->assertSee('value="Ruhrpott Concerts GmbH"', false)
        ->assertSee('Zeche Events · Essen')
        ->assertSee('value="'.$other->getKey().'"', false);
});

it('names the organizer on the event page', function () {
    $external = Event::factory()->for(Organizer::factory()->state(['name' => 'Ruhrpott Concerts GmbH']))->create();
    $own = Event::factory()->create();

    $this->get(route('events.show', $external))
        ->assertSee('Veranstalter')
        ->assertSee('Ruhrpott Concerts GmbH')
        ->assertSee('"organizer":{"@type":"Organization","name":"Ruhrpott Concerts GmbH"', false);

    $this->get(route('events.show', $own))->assertSee('Veranstalter');
});

it('copies the organizer when duplicating an event', function () {
    $event = Event::factory()->external()->create(['social_links' => [['platform' => 'instagram', 'url' => 'https://www.instagram.com/band']]]);

    actingAsAdmin()->post(route('admin.events.duplicate', $event));

    $copy = Event::query()->latest('id')->first();
    expect($copy->organizer_id)->toBe($event->organizer_id)
        ->and($copy->social_links)->toBe($event->social_links);
});
