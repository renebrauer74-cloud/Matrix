<?php

use App\Enums\Location\RoomEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\Pages\PageEnum;
use App\Models\Page;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

/**
 * Formularfelder eines Raums.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function roomFormData(array $overrides = []): array
{
    return array_merge([
        'description' => 'Die große Halle mit Backstage.',
        'size' => '400 m²',
        'capacity' => '850',
        'bars' => '4',
        'stage' => '10 × 8 m',
        'extra' => 'Backstage 200 m²',
    ], $overrides);
}

it('shows the original room details until something is saved', function () {
    $this->get(route('location'))
        ->assertOk()
        ->assertSee('Matrix &amp; Rockpalast', false)
        ->assertSeeInOrder(['Tube', 'Rockpalast', 'Halle 5', 'Bizarre Club', 'Katakomben', "Hell's Kitchen"], false)
        ->assertSee('360 m²')
        ->assertSee('8,5 × 7,5 × 1 m')
        ->assertSee('Vom Sudhaus zum Club')
        ->assertDontSee('Gesamte Matrix');
});

it('lists every room on the admin location page', function () {
    actingAsAdmin()->get(route('admin.pages.edit', PageEnum::Location))
        ->assertOk()
        ->assertSee(route('admin.rooms.edit', 'tube'), false)
        ->assertSee(route('admin.rooms.edit', 'hells-kitchen'), false)
        ->assertSee('bis 700 Personen · 360 m² · 3 Theken')
        ->assertDontSee(route('admin.rooms.edit', 'gesamte-matrix'), false);
});

it('edits the details of a room and shows them everywhere', function () {
    actingAsAdmin()->get(route('admin.rooms.edit', 'tube'))
        ->assertOk()
        ->assertSee('value="360 m²"', false)
        ->assertSee('value="700"', false);

    actingAsAdmin()
        ->put(route('admin.rooms.update', 'tube'), roomFormData())
        ->assertRedirect(route('admin.pages.edit', PageEnum::Location).'#raeume')
        ->assertSessionHas('success', '„Tube“ gespeichert.');

    expect(Room::query()->sole())
        ->key->toBe(RoomEnum::Tube)
        ->size->toBe('400 m²')
        ->capacity->toBe(850)
        ->bars->toBe(4)
        ->stage->toBe('10 × 8 m');

    $this->get(route('location'))->assertSee(['Die große Halle mit Backstage.', '400 m²', '850', '10 × 8 m', 'Backstage 200 m²']);
    $this->get(route('home'))->assertSee('bis 850 Personen');
    $this->get(route('corporate'))
        ->assertSee('bis 850 Personen · 400 m²')
        ->assertSee('Tube (bis 850 Personen)')
        ->assertSee('<dd>850</dd>', false);
});

it('hides optional room facts that were cleared', function () {
    actingAsAdmin()->put(route('admin.rooms.update', 'tube'), roomFormData(['stage' => '', 'extra' => '']))->assertSessionHasNoErrors();

    $this->get(route('location'))
        ->assertDontSee('8,5 × 7,5 × 1 m')
        ->assertDontSee('Backstage 170 m²');
});

it('validates the room details', function () {
    actingAsAdmin()
        ->put(route('admin.rooms.update', 'tube'), roomFormData(['capacity' => 'viele', 'bars' => '']))
        ->assertSessionHasErrors([
            'capacity' => 'Bitte eine Personenzahl angeben, z. B. 700.',
            'bars' => 'Bitte die Anzahl der Theken als Zahl angeben (0 = keine).',
        ]);

    expect(Room::query()->count())->toBe(0);
});

it('uploads and removes a room photo', function () {
    actingAsAdmin()
        ->put(route('admin.rooms.update', 'katakomben'), roomFormData(['image' => UploadedFile::fake()->image('keller.jpg', 1600, 900)]))
        ->assertSessionHasNoErrors();

    $room = Room::query()->sole();
    expect($room->getFirstMedia(MediaCollectionEnum::RoomImage->value))->not->toBeNull();
    $this->get(route('location'))->assertSee('room-images/', false);

    actingAsAdmin()->put(route('admin.rooms.update', 'katakomben'), roomFormData(['remove_image' => '1']))->assertSessionHasNoErrors();

    expect($room->refresh()->getFirstMedia(MediaCollectionEnum::RoomImage->value))->toBeNull();
    $this->get(route('location'))->assertSee('assets/img/katakomben.jpg', false);
});

it('returns 404 for unknown rooms and the whole venue', function (string $anchor) {
    actingAsAdmin()->get(route('admin.rooms.edit', $anchor))->assertNotFound();
})->with(['keller', 'gesamte-matrix']);

it('saves the location page texts and history', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Location), [
        'hero_title' => 'Der Müser-Turm',
        'hero_lead' => 'Sechs Räume unter einem Dach.',
        'history_title' => 'Unsere Geschichte',
        'history' => [
            ['year' => '1806', 'text' => 'Gründung der Brauerei.'],
            ['year' => '', 'text' => ''],
            ['year' => '2026', 'text' => 'Neue Website.'],
        ],
        'cta_title' => 'Jetzt mieten',
        'cta_text' => 'Schreibt uns.',
    ])->assertRedirect(route('admin.pages.edit', PageEnum::Location))->assertSessionHasNoErrors();

    expect(Page::query()->sole()->content['history'])->toBe([
        ['year' => '1806', 'text' => 'Gründung der Brauerei.'],
        ['year' => '2026', 'text' => 'Neue Website.'],
    ]);

    $this->get(route('location'))
        ->assertSee(['Der Müser-Turm', 'Sechs Räume unter einem Dach.', 'Unsere Geschichte', 'Neue Website.', 'Jetzt mieten', 'Schreibt uns.'])
        ->assertDontSee('Vom Sudhaus zum Club');
});

it('keeps guests out of the room administration', function () {
    $this->get(route('admin.rooms.edit', 'tube'))->assertRedirect(route('admin.login'));
    $this->put(route('admin.rooms.update', 'tube'), roomFormData())->assertRedirect(route('admin.login'));

    expect(Room::query()->count())->toBe(0);
});
