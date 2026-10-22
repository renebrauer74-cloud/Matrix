<?php

use App\Actions\Social\DescribeSocialLinkAction;
use App\Enums\Pages\PageEnum;
use App\Models\Event;
use App\Models\Page;

it('saves several links of the same platform for an event and drops empty rows', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'social_links' => [
            ['platform' => 'instagram', 'url' => 'https://www.instagram.com/steelandthunder'],
            ['platform' => 'instagram', 'url' => 'instagram.com/ironpulse'],
            ['platform' => 'youtube', 'url' => ''],
            ['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@steelandthunder'],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->sole()->social_links)->toBe([
        ['platform' => 'instagram', 'url' => 'https://www.instagram.com/steelandthunder'],
        ['platform' => 'instagram', 'url' => 'https://instagram.com/ironpulse'],
        ['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@steelandthunder'],
    ]);
});

it('saves no links when every row is empty', function () {
    actingAsAdmin()->post(route('admin.events.store'), eventFormData([
        'social_links' => [['platform' => 'facebook', 'url' => '']],
    ]))->assertSessionHasNoErrors();

    expect(Event::query()->sole()->social_links)->toBeNull();
});

it('rejects other platforms and invalid links', function () {
    actingAsAdmin()
        ->post(route('admin.events.store'), eventFormData([
            'social_links' => [
                ['platform' => 'twitter', 'url' => 'https://x.com/matrix'],
                ['platform' => 'instagram', 'url' => 'https://kein link'],
            ],
        ]))
        ->assertSessionHasErrors([
            'social_links.0.platform' => 'Bitte Instagram, Facebook, TikTok oder YouTube auswählen.',
            'social_links.1.url' => 'Bitte einen gültigen Link angeben.',
        ]);

    expect(Event::query()->count())->toBe(0);
});

it('shows the stored links as rows in the edit form', function () {
    $event = Event::factory()->create(['social_links' => [
        ['platform' => 'facebook', 'url' => 'https://www.facebook.com/steelandthunder'],
        ['platform' => 'youtube', 'url' => 'https://www.youtube.com/@steelandthunder'],
    ]]);

    actingAsAdmin()->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('name="social_links[1][url]"', false)
        ->assertSee('value="https://www.youtube.com/@steelandthunder"', false)
        ->assertSee('data-next-index="2"', false)
        ->assertSee('Link hinzufügen');
});

it('shows the event links with account names on the event page', function () {
    $event = Event::factory()->create(['social_links' => [
        ['platform' => 'instagram', 'url' => 'https://www.instagram.com/steelandthunder/'],
        ['platform' => 'instagram', 'url' => 'https://www.instagram.com/ironpulse'],
    ]]);

    $this->get(route('events.show', $event))
        ->assertSee('href="https://www.instagram.com/steelandthunder/"', false)
        ->assertSeeInOrder(['@steelandthunder', '@ironpulse']);
});

it('describes a link by its account or else by the platform', function (array $link, string $label) {
    expect(app(DescribeSocialLinkAction::class)->execute($link))->toBe($label);
})->with([
    'instagram' => [['platform' => 'instagram', 'url' => 'https://www.instagram.com/matrixbochum/'], '@matrixbochum'],
    'tiktok' => [['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@matrixbochum'], '@matrixbochum'],
    'youtube handle' => [['platform' => 'youtube', 'url' => 'https://www.youtube.com/@matrixbochum'], '@matrixbochum'],
    'youtube channel' => [['platform' => 'youtube', 'url' => 'https://www.youtube.com/channel/UC123'], 'YouTube'],
    'facebook profile' => [['platform' => 'facebook', 'url' => 'https://www.facebook.com/profile.php?id=123'], 'Facebook'],
    'no path' => [['platform' => 'facebook', 'url' => 'https://www.facebook.com'], 'Facebook'],
]);

it('saves the social media channels of the matrix and shows them in the footer', function () {
    actingAsAdmin()->get(route('admin.pages.edit', PageEnum::Social))
        ->assertOk()
        ->assertSee('Social-Media-Kanäle der Matrix');

    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Social), [
        'social_links' => [
            ['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@matrixbochum'],
            ['platform' => 'youtube', 'url' => 'youtube.com/@matrixbochum'],
        ],
    ])->assertRedirect(route('admin.pages.edit', PageEnum::Social))->assertSessionHasNoErrors();

    expect(Page::query()->sole()->content['social_links'])->toBe([
        ['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@matrixbochum'],
        ['platform' => 'youtube', 'url' => 'https://youtube.com/@matrixbochum'],
    ]);

    $this->get(route('home'))
        ->assertSee('class="footer-social"', false)
        ->assertSeeInOrder(['aria-label="TikTok"', 'aria-label="YouTube"'], false);
});

it('hides the footer icons once every channel is removed', function () {
    config(['matrix.venue.instagram' => 'https://www.instagram.com/matrixbochum']);

    $this->get(route('home'))->assertSee('aria-label="Instagram"', false);

    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Social), ['social_links' => []])->assertSessionHasNoErrors();

    $this->get(route('home'))->assertDontSee('class="footer-social"', false);
});
