<?php

use App\Enums\Pages\PageEnum;
use App\Models\Page;

it('shows the default legal texts on the website', function (string $route, string $text) {
    $this->get(route($route))->assertOk()->assertSee($text);
})->with([
    ['imprint', 'Angaben gemäß § 5 DDG'],
    ['privacy', 'Verantwortlicher'],
]);

it('renders the legal text editors', function (PageEnum $page, string $text) {
    actingAsAdmin()->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee($text)
        ->assertSee('Impressum & Datenschutz');
})->with([
    [PageEnum::Imprint, 'Angaben gemäß § 5 DDG'],
    [PageEnum::Privacy, 'Hinweise zu dieser Website'],
]);

it('saves the imprint and shows it formatted on the website', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Imprint), [
        'body' => "## Anbieter\n\nMatrix GmbH\nMusterweg 1\n\nMail: info@example.org und https://example.org",
    ])->assertRedirect(route('admin.pages.edit', PageEnum::Imprint))
        ->assertSessionHas('success', 'Impressum gespeichert.');

    expect(Page::query()->where('key', PageEnum::Imprint)->sole()->content['body'])->toContain('Matrix GmbH');

    $this->get(route('imprint'))
        ->assertOk()
        ->assertSee('<h2>Anbieter</h2>', false)
        ->assertSee("Matrix GmbH<br>\nMusterweg 1", false)
        ->assertSee('href="mailto:info@example.org"', false)
        ->assertSee('href="https://example.org"', false)
        ->assertDontSee('Angaben gemäß § 5 DDG');
});

it('does not execute html or javascript links in legal texts', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Privacy), [
        'body' => "<script>alert(1)</script>\n\n[Klick](javascript:alert(1))",
    ])->assertSessionHasNoErrors();

    $this->get(route('privacy'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('href="javascript:', false);
});

it('requires a text for legal pages', function () {
    actingAsAdmin()->put(route('admin.pages.update', PageEnum::Privacy), ['body' => ''])
        ->assertSessionHasErrors(['body' => 'Bitte einen Text eingeben.']);
});
