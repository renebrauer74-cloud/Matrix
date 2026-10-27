<?php

use App\Models\Event;

it('uses the capital sharp s in words typed in capital letters', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'title' => 'GROßE NACHT in der Straße',
            'subtitle' => 'MIT DJ WEIß',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->sole())
        ->title->toBe('GROẞE NACHT in der Straße')
        ->subtitle->toBe('MIT DJ WEIẞ');
});

it('shows the capital sharp s in titles set in capital letters', function () {
    $event = Event::factory()->create(['title' => 'Große Straßenparty']);

    $this->get(route('events.index'))->assertSee('Groẞe Straẞenparty');
    $this->get(route('events.show', $event))
        ->assertSee('<h1 class="event-title">Groẞe Straẞenparty</h1>', false)
        ->assertSee('<title>Große Straßenparty', false);
});
