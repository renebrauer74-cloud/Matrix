<?php

namespace App\Http\Requests\Admin;

use App\Enums\Pages\PageEnum;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Texte, Titelbild und Titelvideo einer gepflegten Seite – die Felder hängen von der Seite ab.
 */
class PageContentRequest extends AdminFormRequest
{
    /**
     * Anzahl der Kacheln „Darum bei uns“ auf der Firmenseite.
     */
    public const HIGHLIGHT_SLOTS = 6;

    /**
     * Anzahl der Einträge in der Zeitleiste „Geschichte“ auf der Location-Seite.
     */
    public const HISTORY_SLOTS = 12;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareSocialLinks();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $shared = [
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_lead' => ['nullable', 'string', 'max:400'],
            'intro_title' => ['nullable', 'string', 'max:120'],
            'intro_text' => ['nullable', 'string', 'max:5000'],
            'hero_image' => $this->imageRules(),
            'remove_hero_image' => ['nullable', 'boolean'],
            'hero_video' => $this->videoRules(),
            'remove_hero_video' => ['nullable', 'boolean'],
        ];

        return match ($this->page()) {
            PageEnum::Jobs => [
                ...$shared,
                'benefits' => ['nullable', 'string', 'max:2000'],
                'apply_text' => ['nullable', 'string', 'max:500'],
            ],
            PageEnum::Corporate => [
                ...$shared,
                'highlights' => ['nullable', 'array', 'max:'.self::HIGHLIGHT_SLOTS],
                'highlights.*.title' => ['nullable', 'string', 'max:80'],
                'highlights.*.text' => ['nullable', 'string', 'max:300'],
                'occasions' => ['nullable', 'string', 'max:1000'],
                'cta_title' => ['nullable', 'string', 'max:120'],
                'cta_text' => ['nullable', 'string', 'max:400'],
                'contact_name' => ['nullable', 'string', 'max:120'],
                'contact_phone' => ['nullable', 'string', 'max:60'],
                'contact_email' => ['nullable', 'email', 'max:190'],
            ],
            PageEnum::Location => [
                ...$shared,
                'history_title' => ['nullable', 'string', 'max:120'],
                'history' => ['nullable', 'array', 'max:'.self::HISTORY_SLOTS],
                'history.*.year' => ['nullable', 'string', 'max:20'],
                'history.*.text' => ['nullable', 'string', 'max:400'],
                'cta_title' => ['nullable', 'string', 'max:120'],
                'cta_text' => ['nullable', 'string', 'max:400'],
            ],
            PageEnum::Social => $this->socialLinkRules(),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hero_title.required' => 'Bitte eine Überschrift eingeben.',
            'contact_email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            ...$this->uploadMessages('hero_image', 'hero_video'),
            ...$this->socialLinkMessages(),
        ];
    }

    public function page(): PageEnum
    {
        return $this->route('page');
    }

    /**
     * Die Textfelder der Seite für pages.content – Datei-Felder gehören nicht dazu.
     *
     * @return array<string, mixed>
     */
    public function pageContent(): array
    {
        if ($this->page() === PageEnum::Social) {
            return ['social_links' => $this->socialLinks()];
        }

        $content = collect($this->validated())
            ->except(['hero_image', 'remove_hero_image', 'hero_video', 'remove_hero_video'])
            ->all();

        if ($this->page() === PageEnum::Location) {
            $content['history'] = collect($this->input('history', []))
                ->map(fn (array $entry): array => [
                    'year' => trim((string) ($entry['year'] ?? '')),
                    'text' => trim((string) ($entry['text'] ?? '')),
                ])
                ->filter(fn (array $entry): bool => $entry['year'] !== '' || $entry['text'] !== '')
                ->values()
                ->all();
        }

        if ($this->page() === PageEnum::Corporate) {
            $content['highlights'] = collect($this->input('highlights', []))
                ->map(fn (array $highlight): array => [
                    'title' => trim((string) ($highlight['title'] ?? '')),
                    'text' => trim((string) ($highlight['text'] ?? '')),
                ])
                ->filter(fn (array $highlight): bool => $highlight['title'] !== '')
                ->values()
                ->all();
        }

        return $content;
    }
}
