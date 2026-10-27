<?php

use App\Enums\Events\TicketStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('uploads');
    $this->travelTo('2026-10-27 12:00:00');
});

/**
 * Legt über das Formular eine wöchentliche Serie an (7., 14., 21. November …).
 *
 * @param  array<string, mixed>  $overrides
 * @return Collection<int, Event>
 */
function weeklySeries(int $count = 3, array $overrides = []): Collection
{
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'date' => '2026-11-07',
        'repeat' => 'weekly',
        'repeat_end' => 'count',
        'repeat_count' => $count,
        ...$overrides,
    ]))->assertSessionHasNoErrors();

    return Event::query()->orderBy('starts_at')->get();
}

it('creates a weekly series with genres and image on every date', function () {
    $techno = Genre::factory()->create(['name' => 'Techno', 'slug' => 'techno']);

    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'category' => 'party',
        'title' => 'Bizarre Beats',
        'date' => '2026-11-07',
        'time' => '23:00',
        'end_time' => '05:00',
        'genres' => [$techno->id],
        'image' => UploadedFile::fake()->image('flyer.jpg', 800, 1000),
        'repeat' => 'weekly',
        'repeat_end' => 'count',
        'repeat_count' => 4,
    ]))->assertSessionHas('success', '„Bizarre Beats“ wurde veröffentlicht – plus 3 weitere Termine.');

    $dates = Event::query()->with('genres')->orderBy('starts_at')->get();

    expect($dates)->toHaveCount(4)
        ->and($dates->pluck('series_id')->unique())->toHaveCount(1)
        ->and($dates->first()->series_id)->not->toBeNull()
        ->and($dates->map(fn (Event $event): string => $event->starts_at->format('Y-m-d H:i'))->all())
        ->toBe(['2026-11-07 23:00', '2026-11-14 23:00', '2026-11-21 23:00', '2026-11-28 23:00'])
        ->and($dates->last()->ends_at->format('Y-m-d H:i'))->toBe('2026-11-29 05:00')
        ->and($dates->last()->genres->pluck('name')->all())->toBe(['Techno'])
        ->and($dates->last()->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull();
});

it('creates only the dates ticked in the form', function () {
    weeklySeries(4, ['repeat_js' => '1', 'repeat_dates' => ['2026-11-21', '2026-11-14', '2025-01-01']]);

    expect(Event::query()->orderBy('starts_at')->get()->map(fn (Event $event): string => $event->starts_at->format('Y-m-d'))->all())
        ->toBe(['2026-11-07', '2026-11-14', '2026-11-21']);
});

it('creates freely chosen dates', function () {
    weeklySeries(overrides: ['repeat' => 'custom', 'repeat_dates' => ['2026-12-31', '2026-12-24']]);

    expect(Event::query()->orderBy('starts_at')->get()->map(fn (Event $event): string => $event->starts_at->format('Y-m-d'))->all())
        ->toBe(['2026-11-07', '2026-12-24', '2026-12-31']);
});

it('rejects a recurrence without further dates', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['repeat' => 'custom']))
        ->assertSessionHasErrors(['repeat' => 'Für die Wiederholung sind keine weiteren Termine ausgewählt.']);

    expect(Event::query()->count())->toBe(0);
});

it('applies changes to the following dates but keeps their date and ticket status', function () {
    [, $second] = weeklySeries(3)->all();

    actingAsAdmin()
        ->put(route('admin.events.update', $second), eventFormData([
            'date' => '2026-11-14',
            'time' => '21:00',
            'subtitle' => 'Neues Line-up',
            'ticket_status' => 'soldout',
            'apply_to' => 'following',
        ]))
        ->assertSessionHas('success', 'Änderungen an „Steel & Thunder“ gespeichert und auf 1 weiteren Termin der Serie übertragen.');

    [$first, $second, $third] = Event::query()->orderBy('starts_at')->get()->all();

    expect($first->subtitle)->toBe('Support: Iron Pulse')
        ->and($first->starts_at->format('H:i'))->toBe('20:00')
        ->and($third->subtitle)->toBe('Neues Line-up')
        ->and($third->starts_at->format('Y-m-d H:i'))->toBe('2026-11-21 21:00')
        ->and($second->ticket_status)->toBe(TicketStatusEnum::SoldOut)
        ->and($third->ticket_status)->toBe(TicketStatusEnum::Available);
});

it('applies changes to all dates of a series', function () {
    $dates = weeklySeries(3);

    actingAsAdmin()->put(route('admin.events.update', $dates->last()), eventFormData([
        'date' => '2026-11-21',
        'title' => 'Steel & Thunder Reloaded',
        'apply_to' => 'all',
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->pluck('title')->unique()->all())->toBe(['Steel & Thunder Reloaded'])
        ->and(Event::query()->orderBy('starts_at')->first()->slug)->toBe('steel-und-thunder-reloaded-2026-11-07');
});

it('changes only the edited date by default', function () {
    $dates = weeklySeries(2);

    actingAsAdmin()->put(route('admin.events.update', $dates->first()), eventFormData(['date' => '2026-11-07', 'subtitle' => 'Nur heute']));

    expect($dates->last()->refresh()->subtitle)->toBe('Support: Iron Pulse');
});

it('adds further dates to an existing event as a series', function () {
    $event = Event::factory()->create(['starts_at' => '2026-11-07 20:00:00']);

    actingAsAdmin()
        ->put(route('admin.events.update', $event), eventFormData(['date' => '2026-11-07', 'repeat' => 'biweekly', 'repeat_count' => 3]))
        ->assertSessionHas('success', 'Änderungen an „Steel & Thunder“ gespeichert – 2 neue Termine angelegt.');

    expect($event->refresh()->series_id)->not->toBeNull()
        ->and(Event::query()->where('series_id', $event->series_id)->count())->toBe(3);
});

it('deletes a single date, the following dates or the whole series', function (string $scope, int $remaining, string $message) {
    [, $second] = weeklySeries(3)->all();

    actingAsAdmin()
        ->delete(route('admin.events.destroy', $second), ['scope' => $scope])
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success', $message);

    expect(Event::query()->count())->toBe($remaining);
})->with([
    'single date' => ['this', 2, '„Steel & Thunder“ wurde gelöscht.'],
    'following dates' => ['following', 1, '2 Termine der Serie „Steel & Thunder“ wurden gelöscht.'],
    'whole series' => ['all', 0, '3 Termine der Serie „Steel & Thunder“ wurden gelöscht.'],
]);

it('shows the other dates of the series on the event page', function () {
    [$first, $second, $third] = weeklySeries(3)->all();

    $this->get(route('events.show', $first))
        ->assertOk()
        ->assertSee('Weitere Termine')
        ->assertSee(route('events.show', $second))
        ->assertSee(route('events.show', $third));
});

it('lists all dates of a series in the admin area', function () {
    $dates = weeklySeries(3);

    actingAsAdmin()->get(route('admin.events.index', ['serie' => $dates->first()->series_id]))
        ->assertOk()
        ->assertSee('Serie „Steel & Thunder“ · 3 Termine');
});

it('prefills the form with an earlier event to schedule it again', function () {
    $past = Event::factory()->past()->withImage()->create(['title' => 'Rock Palast Classics', 'price_box' => '6 €']);

    actingAsAdmin()->get(route('admin.events.create', ['vorlage' => $past->id]))
        ->assertOk()
        ->assertSee('Erneut ansetzen: Rock Palast Classics')
        ->assertSee('value="Rock Palast Classics"', false)
        ->assertSee('value="6 €"', false)
        ->assertSee('name="date" type="date" required value=""', false)
        ->assertSee('name="template_id" value="'.$past->id.'"', false);
});

it('schedules an earlier event again with its image but outside its series', function () {
    $past = Event::factory()->past()->withImage()->create(['series_id' => (string) Str::uuid()]);

    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['template_id' => $past->id, 'date' => '2026-12-12']))
        ->assertSessionHasNoErrors();

    $again = Event::query()->latest('id')->first();

    expect($again->series_id)->toBeNull()
        ->and($again->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull()
        ->and($past->refresh()->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull();
});
