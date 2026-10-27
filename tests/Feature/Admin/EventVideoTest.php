<?php

use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

it('stores a YouTube link in its standard form and loads the player only on click', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['video_url' => 'youtu.be/dQw4w9WgXcQ?t=42']))
        ->assertSessionHasNoErrors();

    $event = Event::query()->sole();
    expect($event->video_url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-yt="dQw4w9WgXcQ"', false)
        ->assertDontSee('<iframe', false);
});

it('rejects links that are no YouTube videos', function (string $url) {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['video_url' => $url]))
        ->assertSessionHasErrors(['video_url' => 'Das ist kein gültiger YouTube-Link (z. B. https://www.youtube.com/watch?v=… oder https://youtu.be/…).']);
})->with([
    'other platform' => 'https://vimeo.com/123456',
    'channel instead of video' => 'https://www.youtube.com/@matrixbochum',
]);

it('uploads an own video, shows it and removes it again', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['video' => fakeVideo('aftermovie.mp4')]))
        ->assertSessionHasNoErrors();

    $event = Event::query()->sole();
    expect($event->getFirstMedia(MediaCollectionEnum::EventVideo->value)->file_name)->toBe('aftermovie.mp4');

    $this->get(route('events.show', $event))->assertSee('aftermovie.mp4');

    actingAsAdmin()->put(route('admin.events.update', $event), eventFormData(['remove_video' => '1']));

    expect($event->refresh()->getFirstMedia(MediaCollectionEnum::EventVideo->value))->toBeNull();
});

it('rejects images as video', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData(['video' => UploadedFile::fake()->image('flyer.jpg')]))
        ->assertSessionHasErrors(['video' => 'Bitte ein Video als MP4, WebM oder MOV hochladen.']);
});

it('gives every date of a series the video', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'video' => fakeVideo(),
        'repeat' => 'weekly',
        'repeat_count' => 2,
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->get()->every(fn (Event $event): bool => $event->hasMedia(MediaCollectionEnum::EventVideo->value)))->toBeTrue();
});
