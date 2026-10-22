<?php

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;

it('renders every public page while the database is empty', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with(['home', 'events.index', 'location', 'directions', 'jobs', 'corporate', 'contact', 'lost-items', 'imprint', 'privacy']);

it('links the jobs page in the main navigation and marks it active there', function () {
    $response = $this->get(route('jobs'))->assertOk();

    expect($response->getContent())
        ->toMatch('~<a href="'.preg_quote(route('jobs'), '~').'"\s+aria-current="page"\s*>Jobs</a>~');
});

it('renders the category pages of the programme', function (string $slug) {
    $this->get(route('events.index', $slug))->assertOk();
})->with(['konzerte', 'partys', 'sonstiges']);

it('rejects unknown programme categories', function () {
    $this->get('/programm/gibtsnicht')->assertNotFound();
});

it('redirects the old short urls to the programme', function () {
    $this->get('/konzerte')->assertRedirect('/programm/konzerte');
    $this->get('/partys')->assertRedirect('/programm/partys');
});

it('lists upcoming published events in chronological order', function () {
    Event::factory()->create(['title' => 'Später', 'starts_at' => now()->addDays(10)]);
    Event::factory()->create(['title' => 'Früher', 'starts_at' => now()->addDays(2)]);
    Event::factory()->draft()->create(['title' => 'Geheimer Entwurf']);
    Event::factory()->past()->create(['title' => 'Längst vorbei']);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSeeInOrder(['Früher', 'Später'])
        ->assertDontSee('Geheimer Entwurf')
        ->assertDontSee('Längst vorbei');
});

it('keeps an event without end time listed for six hours after it started', function () {
    Event::factory()->create(['title' => 'Läuft noch', 'starts_at' => now()->subHours(5), 'ends_at' => null]);
    Event::factory()->create(['title' => 'Ist vorbei', 'starts_at' => now()->subHours(7), 'ends_at' => null]);

    $this->get(route('events.index'))
        ->assertSee('Läuft noch')
        ->assertDontSee('Ist vorbei');
});

it('filters the programme by category and room', function () {
    Event::factory()->category(EventCategoryEnum::Konzert)->create(['title' => 'Metal-Abend', 'room' => 'Tube']);
    Event::factory()->category(EventCategoryEnum::Party)->create(['title' => 'Clubnacht', 'room' => 'Katakomben']);

    $this->get(route('events.index', 'konzerte'))
        ->assertSee('Metal-Abend')
        ->assertDontSee('Clubnacht');

    $this->get(route('events.index', ['raum' => 'Katakomben']))
        ->assertSee('Clubnacht')
        ->assertDontSee('Metal-Abend');
});

it('shows featured events first in the home slider', function () {
    Event::factory()->create(['title' => 'Normal und früh', 'starts_at' => now()->addDay()]);
    Event::factory()->featured()->create(['title' => 'Highlight und spät', 'starts_at' => now()->addWeeks(2)]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Highlight und spät', 'Normal und früh']);
});

it('shows the free label of other events instead of the category', function () {
    Event::factory()->category(EventCategoryEnum::Sonstiges)->create(['category_label' => 'Lesung']);

    $this->get(route('events.index'))->assertSee('Lesung');
});

it('shows an event with structured data and formatted description', function () {
    $event = Event::factory()->category(EventCategoryEnum::Konzert)->create([
        'title' => 'Dirty Honey',
        'description' => "Erster Absatz mit **fett**.\n\nMehr unter https://example.com",
        'price_presale' => '24 €',
        'price_box' => '29 €',
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('"@type":"MusicEvent"', false)
        ->assertSee('<strong>fett</strong>', false)
        ->assertSee('<a href="https://example.com" target="_blank" rel="noopener">', false)
        ->assertSee('VVK 24 € · AK 29 €');
});

it('escapes html in the event description', function () {
    $event = Event::factory()->create(['description' => '<script>alert(1)</script>']);

    $this->get(route('events.show', $event))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('offers tickets only while tickets are available', function () {
    $soldOut = Event::factory()->create(['ticket_status' => TicketStatusEnum::SoldOut, 'ticket_url' => 'https://tickets.example.com/x']);

    $this->get(route('events.show', $soldOut))
        ->assertSee('Ausverkauft')
        ->assertDontSee('Tickets kaufen');
});

it('hides drafts from guests but shows them to admins', function () {
    $draft = Event::factory()->draft()->create();

    $this->get(route('events.show', $draft))->assertNotFound();

    actingAsAdmin()
        ->get(route('events.show', $draft))
        ->assertOk()
        ->assertSee('Entwurf – nur für eingeloggte Admins sichtbar.');
});

it('downloads a calendar entry for an event', function () {
    $event = Event::factory()->create([
        'title' => 'Neon Nights',
        'starts_at' => '2026-12-05 23:00:00',
        'ends_at' => null,
    ]);

    $response = $this->get(route('events.calendar', $event));

    $response->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect($response->getContent())
        ->toContain('SUMMARY:Neon Nights')
        ->toContain('DTSTART:20261205T220000Z')
        ->toContain('DTEND:20261206T020000Z');
});

it('shows uploaded event images in two sizes', function () {
    Storage::fake('uploads');
    $event = Event::factory()->withImage()->create();

    $this->get(route('events.show', $event))
        ->assertSee('-md.webp 760w', false)
        ->assertSee('-lg.webp 1600w', false);
});
