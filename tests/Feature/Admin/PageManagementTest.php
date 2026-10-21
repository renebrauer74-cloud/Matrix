<?php

use App\Enums\MediaCollectionEnum;
use App\Enums\Pages\PageEnum;
use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

/**
 * Minimaler MP4-Kopf, damit die Media Library die Datei als video/mp4 erkennt.
 */
function fakeVideo(string $name = 'clip.mp4'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 512));
}

it('renders both page editors with the default texts', function (PageEnum $page, string $text) {
    actingAsAdmin()->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee($text);
})->with([
    [PageEnum::Jobs, 'Werde Teil der Crew'],
    [PageEnum::Corporate, 'Eure Firmenfeier im Müser-Turm'],
]);

it('saves the jobs page texts and shows them on the website', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Jobs), [
        'hero_title' => 'Komm ins Team',
        'hero_lead' => 'Wir suchen Verstärkung.',
        'intro_title' => 'Warum wir',
        'intro_text' => 'Weil es Spaß macht.',
        'benefits' => "Trinkgeld\nGutes Team",
        'apply_text' => 'Wir melden uns schnell.',
    ])->assertRedirect(route('admin.pages.edit', PageEnum::Jobs));

    $this->get(route('jobs'))->assertSee(['Komm ins Team', 'Wir suchen Verstärkung.', 'Trinkgeld', 'Gutes Team', 'Wir melden uns schnell.']);
});

it('saves corporate highlights without empty slots', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Corporate), [
        'hero_title' => 'Feiern im Turm',
        'highlights' => [
            ['title' => 'Große Bühne', 'text' => 'Mit Licht.'],
            ['title' => '', 'text' => 'Ohne Titel fliegt raus.'],
            ['title' => 'Catering', 'text' => ''],
        ],
        'occasions' => "Sommerfest\nKick-off",
        'contact_email' => 'events@example.com',
    ])->assertSessionHasNoErrors();

    expect(Page::query()->sole()->content['highlights'])->toBe([
        ['title' => 'Große Bühne', 'text' => 'Mit Licht.'],
        ['title' => 'Catering', 'text' => ''],
    ]);

    $this->get(route('corporate'))
        ->assertSee(['Feiern im Turm', 'Große Bühne', 'Kick-off', 'events@example.com'])
        ->assertDontSee('Ohne Titel fliegt raus.');
});

it('requires a headline', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Corporate), ['hero_title' => ''])
        ->assertSessionHasErrors('hero_title');
});

it('uploads a hero image and video and removes them again', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Corporate), [
        'hero_title' => 'Feiern im Turm',
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 2400, 1200),
        'hero_video' => fakeVideo(),
    ])->assertSessionHasNoErrors();

    $page = Page::query()->sole();
    expect($page->getFirstMedia(MediaCollectionEnum::PageHero->value))->not->toBeNull()
        ->and($page->getFirstMedia(MediaCollectionEnum::PageHeroVideo->value)->file_name)->toBe('clip.mp4');

    $this->get(route('corporate'))->assertSee('<video src="', false);

    $this->put(route('admin.pages.update', PageEnum::Corporate), [
        'hero_title' => 'Feiern im Turm',
        'remove_hero_image' => '1',
        'remove_hero_video' => '1',
    ]);

    expect($page->fresh()->getMedia(MediaCollectionEnum::PageHero->value))->toBeEmpty()
        ->and($page->fresh()->getMedia(MediaCollectionEnum::PageHeroVideo->value))->toBeEmpty();
});

it('uploads several gallery images and shows them instead of the room photos', function () {
    actingAsAdmin()->post(route('admin.pages.media.store', PageEnum::Corporate), [
        'collection' => MediaCollectionEnum::PageGallery->value,
        'files' => [
            UploadedFile::fake()->image('eins.jpg', 1600, 1000),
            UploadedFile::fake()->image('zwei.jpg', 1600, 1000),
        ],
    ])->assertSessionHas('success', '2 Bilder hochgeladen.');

    expect(Page::query()->sole()->getMedia(MediaCollectionEnum::PageGallery->value))->toHaveCount(2);

    $this->get(route('corporate'))
        ->assertSee('page-gallery/', false)
        ->assertDontSee('<a class="gallery-item" href="'.asset('assets/img/katakomben.jpg').'"', false);
});

it('uploads videos to the video list', function () {
    actingAsAdmin()->post(route('admin.pages.media.store', PageEnum::Corporate), [
        'collection' => MediaCollectionEnum::PageVideos->value,
        'files' => [fakeVideo('aftermovie.mp4')],
    ])->assertSessionHas('success', '1 Video hochgeladen.');

    $this->get(route('corporate'))->assertSee('aftermovie.mp4');
});

it('rejects images in the video list and other collections', function () {
    actingAsAdmin()->post(route('admin.pages.media.store', PageEnum::Corporate), [
        'collection' => MediaCollectionEnum::PageVideos->value,
        'files' => [UploadedFile::fake()->image('foto.jpg')],
    ])->assertSessionHasErrors('files.0');

    $this->post(route('admin.pages.media.store', PageEnum::Corporate), [
        'collection' => MediaCollectionEnum::Events->value,
        'files' => [UploadedFile::fake()->image('foto.jpg')],
    ])->assertSessionHasErrors('collection');
});

it('reorders and deletes gallery images', function () {
    actingAsAdmin()->post(route('admin.pages.media.store', PageEnum::Corporate), [
        'collection' => MediaCollectionEnum::PageGallery->value,
        'files' => [UploadedFile::fake()->image('eins.jpg', 800, 600), UploadedFile::fake()->image('zwei.jpg', 800, 600)],
    ]);
    $page = Page::query()->sole();
    [$first, $second] = $page->getMedia(MediaCollectionEnum::PageGallery->value)->all();

    $this->post(route('admin.pages.media.move', [PageEnum::Corporate, $second->id]), ['richtung' => 'vor']);

    expect($page->fresh()->getMedia(MediaCollectionEnum::PageGallery->value)->pluck('file_name')->all())->toBe(['zwei.jpg', 'eins.jpg']);

    $this->delete(route('admin.pages.media.destroy', [PageEnum::Corporate, $first->id]));

    expect($page->fresh()->getMedia(MediaCollectionEnum::PageGallery->value)->pluck('file_name')->all())->toBe(['zwei.jpg']);
});

it('only touches media that belongs to the page', function () {
    $event = Event::factory()->withImage()->create();
    $foreign = $event->getFirstMedia(MediaCollectionEnum::Events->value);

    actingAsAdmin()->delete(route('admin.pages.media.destroy', [PageEnum::Corporate, $foreign->id]))->assertNotFound();

    expect($event->fresh()->getFirstMedia(MediaCollectionEnum::Events->value))->not->toBeNull();
});
