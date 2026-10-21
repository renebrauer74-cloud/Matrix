<?php

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

it('lists events with filters and search', function () {
    Event::factory()->category(EventCategoryEnum::Konzert)->create(['title' => 'Metal-Abend']);
    Event::factory()->category(EventCategoryEnum::Party)->create(['title' => 'Clubnacht']);
    Event::factory()->past()->create(['title' => 'Längst vorbei']);
    Event::factory()->draft()->create(['title' => 'Entwurf-Event']);

    actingAsAdmin()->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee(['Metal-Abend', 'Clubnacht', 'Entwurf-Event'])
        ->assertDontSee('Längst vorbei');

    $this->get(route('admin.events.index', ['ansicht' => 'past']))->assertSee('Längst vorbei')->assertDontSee('Metal-Abend');
    $this->get(route('admin.events.index', ['ansicht' => 'drafts']))->assertSee('Entwurf-Event')->assertDontSee('Clubnacht');
    $this->get(route('admin.events.index', ['kategorie' => 'party']))->assertSee('Clubnacht')->assertDontSee('Metal-Abend');
    $this->get(route('admin.events.index', ['q' => 'metal']))->assertSee('Metal-Abend')->assertDontSee('Clubnacht');
});

it('renders the create form with a preset category', function () {
    actingAsAdmin()->get(route('admin.events.create', ['art' => 'party']))
        ->assertOk()
        ->assertSee('value="party" checked', false);
});

it('creates an event with slug, image and author', function () {
    $admin = actingAsAdmin();

    $response = $admin->post(route('admin.events.store'), eventFormData([
        'date' => '2026-11-14',
        'image' => UploadedFile::fake()->image('flyer.jpg', 1200, 1600),
    ]));

    $event = Event::query()->sole();
    $response->assertRedirect(route('admin.events.edit', $event))->assertSessionHas('success');

    expect($event)
        ->slug->toBe('steel-und-thunder-2026-11-14')
        ->starts_at->format('Y-m-d H:i:s')->toBe('2026-11-14 20:00:00')
        ->doors_at->toStartWith('19:00')
        ->room->toBe('Tube')
        ->created_by->toBe(auth()->id())
        ->and($event->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull();
});

it('requires the kind of event for other events', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['category' => 'sonstiges', 'category_label' => '']))
        ->assertSessionHasErrors(['category_label' => 'Bitte angeben, was für eine Veranstaltung es ist (z. B. Lesung).']);

    expect(Event::query()->count())->toBe(0);
});

it('validates title, date and ticket link', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['title' => '', 'date' => '31.12.2026', 'ticket_url' => 'kein link mit leerzeichen']))
        ->assertSessionHasErrors(['title', 'date', 'ticket_url']);
});

it('adds https to ticket links without scheme', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData(['ticket_url' => 'matrix.ticket.io/abc']));

    expect(Event::query()->sole()->ticket_url)->toBe('https://matrix.ticket.io/abc');
});

it('moves the end after midnight to the next day', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData(['date' => '2026-11-14', 'time' => '23:00', 'end_time' => '05:00']));

    expect(Event::query()->sole()->ends_at->format('Y-m-d H:i'))->toBe('2026-11-15 05:00');
});

it('saves a custom room', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData(['room' => '__custom', 'room_custom' => 'Biergarten']));

    expect(Event::query()->sole()->room)->toBe('Biergarten');
});

it('redirects to a new form when saving and creating another', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['after' => 'new']))
        ->assertRedirect(route('admin.events.create'));
});

it('updates an event and removes its image', function () {
    $event = Event::factory()->withImage()->create(['title' => 'Alt']);

    actingAsAdmin()
        ->put(route('admin.events.update', $event), eventFormData(['title' => 'Neu', 'remove_image' => '1']))
        ->assertRedirect(route('admin.events.edit', $event));

    $event->refresh();
    expect($event->title)->toBe('Neu')
        ->and($event->slug)->toStartWith('neu-')
        ->and($event->getFirstMedia(MediaCollectionEnum::Events->value))->toBeNull();
});

it('renders the edit form', function () {
    $event = Event::factory()->withImage()->create(['title' => 'Bearbeitbar']);

    actingAsAdmin()->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('Bearbeitbar')
        ->assertSee('Bild entfernen');
});

it('duplicates an event with its image as draft', function () {
    $event = Event::factory()->featured()->withImage()->create(['title' => 'Original']);

    actingAsAdmin()->post(route('admin.events.duplicate', $event))->assertSessionHas('success');

    $copy = Event::query()->whereKeyNot($event->getKey())->sole();
    expect($copy)
        ->title->toBe('Original (Kopie)')
        ->status->toBe(EventStatusEnum::Draft)
        ->is_featured->toBeFalse()
        ->slug->not->toBe($event->slug)
        ->and($copy->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull();
});

it('toggles the publication status', function () {
    $event = Event::factory()->create();

    actingAsAdmin()->post(route('admin.events.status', $event));
    expect($event->fresh()->status)->toBe(EventStatusEnum::Draft);

    $this->post(route('admin.events.status', $event));
    expect($event->fresh()->status)->toBe(EventStatusEnum::Published);
});

it('deletes an event with its image', function () {
    $event = Event::factory()->withImage()->create();

    actingAsAdmin()->delete(route('admin.events.destroy', $event))->assertRedirect(route('admin.events.index'));

    expect(Event::query()->count())->toBe(0);
    Storage::disk('uploads')->assertDirectoryEmpty(MediaCollectionEnum::Events->value);
});
