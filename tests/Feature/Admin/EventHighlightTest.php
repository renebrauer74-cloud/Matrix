<?php

use App\Models\Event;

beforeEach(function () {
    $this->travelTo('2026-10-27 12:00:00');
});

it('starts a highlight the chosen time before the event', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'date' => '2026-12-05',
            'time' => '20:00',
            'is_featured' => '1',
            'featured_start' => '14',
            'featured_end' => 'event',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->sole())
        ->featured_lead_days->toBe(14)
        ->featured_from->format('Y-m-d H:i')->toBe('2026-11-21 20:00')
        ->featured_until->toBeNull();
});

it('stores a highlight window with own dates', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'date' => '2026-12-05',
        'is_featured' => '1',
        'featured_start' => 'custom',
        'featured_from_date' => '2026-11-01',
        'featured_from_time' => '10:00',
        'featured_end' => 'custom',
        'featured_until_date' => '2026-11-30',
        'featured_until_time' => '23:59',
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->sole())
        ->featured_lead_days->toBeNull()
        ->featured_from->format('Y-m-d H:i')->toBe('2026-11-01 10:00')
        ->featured_until->format('Y-m-d H:i')->toBe('2026-11-30 23:59');
});

it('rejects a highlight that ends before it starts', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'date' => '2026-12-05',
            'is_featured' => '1',
            'featured_start' => '7',
            'featured_end' => 'custom',
            'featured_until_date' => '2026-11-20',
            'featured_until_time' => '12:00',
        ]))
        ->assertSessionHasErrors(['featured_until_date' => 'Das Highlight-Ende muss nach dem Start liegen.']);
});

it('ignores the highlight window when the event is no highlight', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'featured_start' => 'custom',
        'featured_from_date' => 'kaputt',
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->sole())->featured_from->toBeNull()->featured_lead_days->toBeNull();
});

it('puts a highlight first in the home carousel only during its window', function () {
    Event::factory()->create(['title' => 'Normal und früher', 'starts_at' => now()->addDays(20)]);
    Event::factory()->featured()->create([
        'title' => 'Geplantes Highlight',
        'starts_at' => now()->addDays(21),
        'featured_from' => now()->addWeek(),
        'featured_until' => now()->addWeeks(2),
    ]);

    $this->get(route('home'))->assertSeeInOrder(['Normal und früher', 'Geplantes Highlight']);

    $this->travel(8)->days();
    $this->get(route('home'))->assertSeeInOrder(['Geplantes Highlight', 'Normal und früher']);

    $this->travel(7)->days();
    $this->get(route('home'))->assertSeeInOrder(['Normal und früher', 'Geplantes Highlight']);
});

it('preselects the highlight start in the edit form', function () {
    $event = Event::factory()->featured()->create(['featured_lead_days' => 7, 'featured_from' => now()->addDay()]);

    actingAsAdmin()->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('value="7" selected', false);
});

it('marks a highlight that has not started yet as planned in the admin list', function () {
    Event::factory()->featured()->create(['featured_from' => now()->addWeek()]);

    actingAsAdmin()->get(route('admin.events.index'))->assertSee('geplant');
});
