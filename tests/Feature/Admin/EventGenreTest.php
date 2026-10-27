<?php

use App\Enums\Events\EventCategoryEnum;
use App\Models\Event;
use App\Models\Genre;

it('creates an event with chosen and newly typed genres', function () {
    $metal = Genre::factory()->create(['name' => 'Metal', 'slug' => 'metal']);

    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'genres' => [$metal->id],
            'new_genres' => ['Thrash Metal', '  metal  ', ''],
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->sole()->genres->pluck('name')->all())->toBe(['Metal', 'Thrash Metal'])
        ->and(Genre::query()->count())->toBe(2);
});

it('keeps the free label only for other events', function (string $category, ?string $expected) {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData(['category' => $category, 'category_label' => 'Lesung']));

    expect(Event::query()->sole()->category_label)->toBe($expected);
})->with([
    'concert' => ['konzert', null],
    'party' => ['party', null],
    'other' => ['sonstiges', 'Lesung'],
]);

it('rejects genres that do not exist', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['genres' => [999]]))
        ->assertSessionHasErrors(['genres.0' => 'Bitte nur Genres aus der Liste auswählen.']);
});

it('shows the genres on the card and links them on the event page', function () {
    $event = Event::factory()->category(EventCategoryEnum::Konzert)->create(['title' => 'Steel & Thunder']);
    $event->genres()->attach([
        Genre::factory()->create(['name' => 'Metal', 'slug' => 'metal'])->id,
        Genre::factory()->create(['name' => 'Punk', 'slug' => 'punk'])->id,
        Genre::factory()->create(['name' => 'Thrash', 'slug' => 'thrash'])->id,
    ]);

    $this->get(route('events.index'))->assertSee('Metal · Punk +1');
    $this->get(route('events.show', $event))->assertSee(route('events.index', ['genre' => 'thrash']), false);
});

it('filters the programme by genre', function () {
    $techno = Genre::factory()->create(['name' => 'Techno', 'slug' => 'techno']);
    Event::factory()->create(['title' => 'Bizarre Beats'])->genres()->attach($techno);
    Event::factory()->create(['title' => 'Dark Romance']);

    $this->get(route('events.index', ['genre' => 'techno']))
        ->assertOk()
        ->assertSee('Bizarre Beats')
        ->assertDontSee('Dark Romance');

    $this->get(route('events.index', ['genre' => 'unbekannt']))->assertSee('Dark Romance');
});

it('removes genres that no event uses any more', function () {
    $shared = Genre::factory()->create();
    $single = Genre::factory()->create();
    $event = Event::factory()->create();
    $event->genres()->attach([$shared->id, $single->id]);
    Event::factory()->create()->genres()->attach($shared);

    actingAsAdmin()->delete(route('admin.events.destroy', $event));

    expect(Genre::query()->pluck('id')->all())->toBe([$shared->id]);
});

it('finds events by genre in the admin search', function () {
    Event::factory()->create(['title' => 'Bizarre Beats'])->genres()->attach(Genre::factory()->create(['name' => 'Techno']));
    Event::factory()->create(['title' => 'Dark Romance']);

    actingAsAdmin()->get(route('admin.events.index', ['q' => 'techno']))
        ->assertSee('Bizarre Beats')
        ->assertDontSee('Dark Romance');
});
