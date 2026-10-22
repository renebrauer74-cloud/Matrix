<?php

use App\Enums\Events\EventStatusEnum;
use App\Models\Event;

it('schedules an event to go online later', function () {
    $this->travelTo('2026-10-22 12:00:00');

    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'date' => '2026-12-05',
            'status' => 'scheduled',
            'publish_date' => '2026-10-25',
            'publish_time' => '18:00',
        ]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', '„Steel & Thunder“ wurde gespeichert und geht am 25.10.2026 um 18:00 Uhr online.');

    $event = Event::query()->sole();
    expect($event->status)->toBe(EventStatusEnum::Published)
        ->and($event->publish_at->format('Y-m-d H:i'))->toBe('2026-10-25 18:00')
        ->and($event->isScheduled())->toBeTrue();
});

it('keeps a scheduled event off the website until it goes online', function () {
    $this->travelTo('2026-10-22 12:00:00');
    $event = Event::factory()->scheduled(now()->setDateTime(2026, 10, 25, 18, 0))->create([
        'title' => 'Geheimkonzert',
        'starts_at' => '2026-12-05 20:00:00',
    ]);

    $this->get(route('events.index'))->assertDontSee('Geheimkonzert');
    $this->get(route('home'))->assertDontSee('Geheimkonzert');
    $this->get(route('events.show', $event))->assertNotFound();

    actingAsAdmin()->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Geplant – geht am 25.10.2026 um 18:00 Uhr online');

    auth()->logout();
    $this->travelTo('2026-10-25 18:00:00');

    $this->get(route('events.index'))->assertSee('Geheimkonzert');
    $this->get(route('events.show', $event))->assertOk()->assertDontSee('Geplant – geht am');
});

it('requires a publication time in the future and before the event starts', function (array $data, string $message) {
    $this->travelTo('2026-10-22 12:00:00');

    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['date' => '2026-12-05', 'time' => '20:00', 'status' => 'scheduled', ...$data]))
        ->assertSessionHasErrors(['publish_date' => $message]);

    expect(Event::query()->count())->toBe(0);
})->with([
    'missing' => [['publish_date' => '', 'publish_time' => ''], 'Bitte angeben, wann die Veranstaltung online gehen soll.'],
    'past' => [['publish_date' => '2026-10-21', 'publish_time' => '10:00'], 'Der Zeitpunkt liegt schon in der Vergangenheit – dann einfach „Online“ wählen.'],
    'after start' => [['publish_date' => '2026-12-05', 'publish_time' => '21:00'], 'Die Veranstaltung muss vor ihrem Beginn online gehen.'],
]);

it('ignores the publication time for online events and drafts', function (string $status) {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['status' => $status, 'publish_date' => '2000-01-01', 'publish_time' => 'kaputt']))
        ->assertSessionHasNoErrors();

    expect(Event::query()->sole()->publish_at)->toBeNull();
})->with(['published', 'draft']);

it('preselects the schedule in the edit form', function () {
    $event = Event::factory()->scheduled(now()->addDays(3)->setTime(18, 30))->create();

    actingAsAdmin()->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('value="scheduled" checked', false)
        ->assertSee('value="'.now()->addDays(3)->format('Y-m-d').'"', false)
        ->assertSee('value="18:30"', false);
});

it('publishes a scheduled event right away when switched to online', function () {
    $event = Event::factory()->scheduled()->create();

    actingAsAdmin()->put(route('admin.events.update', $event), eventFormData(['status' => 'published']))->assertSessionHasNoErrors();

    expect($event->refresh()->publish_at)->toBeNull()
        ->and($event->isOnline())->toBeTrue();
});

it('lists scheduled events in their own tab and publishes them from the list', function () {
    $scheduled = Event::factory()->scheduled()->create(['title' => 'Kommt bald']);
    Event::factory()->create(['title' => 'Schon online']);

    actingAsAdmin()->get(route('admin.events.index', ['ansicht' => 'scheduled']))
        ->assertOk()
        ->assertSee('Kommt bald')
        ->assertSee('Sofort veröffentlichen')
        ->assertDontSee('Schon online');

    actingAsAdmin()->post(route('admin.events.status', $scheduled));

    expect($scheduled->refresh()->isOnline())->toBeTrue()
        ->and($scheduled->publish_at)->toBeNull();
});

it('does not copy the schedule when duplicating', function () {
    $event = Event::factory()->scheduled()->create();

    actingAsAdmin()->post(route('admin.events.duplicate', $event));

    expect(Event::query()->latest('id')->first())
        ->isDraft()->toBeTrue()
        ->publish_at->toBeNull();
});
