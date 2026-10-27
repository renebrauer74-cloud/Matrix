<?php

namespace App\Http\Requests\Admin;

use App\Actions\Events\CalculateRecurrenceDatesAction;
use App\Actions\Events\ExtractYoutubeIdAction;
use App\Actions\Events\ResolveSeriesDatesAction;
use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventOrganizerEnum;
use App\Enums\Events\EventPublicationEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\HighlightEndEnum;
use App\Enums\Events\HighlightStartEnum;
use App\Enums\Events\ImageFocusEnum;
use App\Enums\Events\RecurrenceEndEnum;
use App\Enums\Events\RecurrenceEnum;
use App\Enums\Events\SeriesScopeEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\Location\RoomEnum;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventRequest extends AdminFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $ticketUrl = trim((string) $this->input('ticket_url'));

        if ($ticketUrl !== '' && ! preg_match('#^https?://#i', $ticketUrl)) {
            $this->merge(['ticket_url' => 'https://'.$ticketUrl]);
        }

        $organizerWebsite = trim((string) $this->input('organizer_website'));

        if ($organizerWebsite !== '' && ! preg_match('#^https?://#i', $organizerWebsite)) {
            $this->merge(['organizer_website' => 'https://'.$organizerWebsite]);
        }

        $videoUrl = trim((string) $this->input('video_url'));

        if ($videoUrl !== '' && ! preg_match('#^https?://#i', $videoUrl)) {
            $this->merge(['video_url' => 'https://'.$videoUrl]);
        }

        // Caps Lock: „GROßE NACHT“ → „GROẞE NACHT“
        foreach (['title', 'subtitle', 'category_label'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => normalize_caps($this->input($field))]);
            }
        }

        $this->prepareSocialLinks();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(EventCategoryEnum::class)],
            'category_label' => ['nullable', 'string', 'max:80', Rule::requiredIf($this->input('category') === EventCategoryEnum::Sonstiges->value)],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65000'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'doors' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'rooms' => ['nullable', 'array'],
            'rooms.*' => [Rule::enum(RoomEnum::class)],
            'room_custom' => ['nullable', 'string', 'max:80'],
            'organizer' => ['required', Rule::enum(EventOrganizerEnum::class)],
            ...array_map(fn (array $rules): array => ['exclude_unless:organizer,'.EventOrganizerEnum::External->value, ...$rules], [
                'organizer_id' => ['nullable', 'integer', Rule::exists('organizers', 'id')],
                'organizer_name' => ['required', 'string', 'max:150'],
                'organizer_contact_person' => ['nullable', 'string', 'max:120'],
                'organizer_street' => ['nullable', 'string', 'max:150'],
                'organizer_zip' => ['nullable', 'string', 'max:10'],
                'organizer_city' => ['nullable', 'string', 'max:100'],
                'organizer_phone' => ['nullable', 'string', 'max:60'],
                'organizer_email' => ['nullable', 'email', 'max:190'],
                'organizer_website' => ['nullable', 'url:http,https', 'max:500'],
            ]),
            ...$this->socialLinkRules(),
            'genres' => ['nullable', 'array', 'max:12'],
            'genres.*' => ['integer', Rule::exists('genres', 'id')],
            'new_genres' => ['nullable', 'array', 'max:12'],
            'new_genres.*' => ['nullable', 'string', 'max:60'],
            'image' => $this->imageRules(),
            'remove_image' => ['nullable', 'boolean'],
            'video' => $this->videoRules(),
            'remove_video' => ['nullable', 'boolean'],
            'video_url' => ['nullable', 'url:http,https', 'max:500', function (string $attribute, mixed $value, Closure $fail): void {
                if ((new ExtractYoutubeIdAction)->execute((string) $value) === null) {
                    $fail('Das ist kein gültiger YouTube-Link (z. B. https://www.youtube.com/watch?v=… oder https://youtu.be/…).');
                }
            }],
            'repeat' => ['nullable', Rule::enum(RecurrenceEnum::class)],
            'repeat_end' => ['nullable', Rule::enum(RecurrenceEndEnum::class)],
            'repeat_count' => ['nullable', 'integer', 'min:2', 'max:52'],
            'repeat_until' => ['nullable', 'date_format:Y-m-d'],
            'repeat_dates' => ['nullable', 'array', 'max:'.CalculateRecurrenceDatesAction::MAX_DATES],
            'repeat_dates.*' => ['date_format:Y-m-d'],
            'repeat_js' => ['nullable', 'boolean'],
            'apply_to' => ['nullable', Rule::enum(SeriesScopeEnum::class)],
            'template_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
            'image_focus' => ['nullable', Rule::enum(ImageFocusEnum::class)],
            'ticket_url' => ['nullable', 'url', 'max:500'],
            'price_presale' => ['nullable', 'string', 'max:60'],
            'price_box' => ['nullable', 'string', 'max:60'],
            'age_rating' => ['nullable', 'string', 'max:40'],
            'ticket_status' => ['nullable', Rule::enum(TicketStatusEnum::class)],
            'status' => ['required', Rule::enum(EventPublicationEnum::class)],
            'publish_date' => ['exclude_unless:status,'.EventPublicationEnum::Scheduled->value, 'required', 'date_format:Y-m-d'],
            'publish_time' => ['exclude_unless:status,'.EventPublicationEnum::Scheduled->value, 'required', 'date_format:H:i'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_start' => ['exclude_unless:is_featured,1', 'nullable', Rule::enum(HighlightStartEnum::class)],
            'featured_from_date' => ['exclude_unless:is_featured,1', 'exclude_unless:featured_start,'.HighlightStartEnum::Custom->value, 'required', 'date_format:Y-m-d'],
            'featured_from_time' => ['exclude_unless:is_featured,1', 'exclude_unless:featured_start,'.HighlightStartEnum::Custom->value, 'required', 'date_format:H:i'],
            'featured_end' => ['exclude_unless:is_featured,1', 'nullable', Rule::enum(HighlightEndEnum::class)],
            'featured_until_date' => ['exclude_unless:is_featured,1', 'exclude_unless:featured_end,'.HighlightEndEnum::Custom->value, 'required', 'date_format:Y-m-d'],
            'featured_until_time' => ['exclude_unless:is_featured,1', 'exclude_unless:featured_end,'.HighlightEndEnum::Custom->value, 'required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Bitte eine Art auswählen.',
            'category_label.required' => 'Bitte angeben, was für eine Veranstaltung es ist (z. B. Lesung).',
            'title.required' => 'Bitte einen Titel eingeben.',
            'date.*' => 'Bitte gültiges Datum und Uhrzeit angeben.',
            'time.*' => 'Bitte gültiges Datum und Uhrzeit angeben.',
            'doors.date_format' => 'Ungültige Uhrzeit beim Einlass.',
            'end_time.date_format' => 'Ungültige Uhrzeit beim Ende.',
            'rooms.*' => 'Bitte nur Räume aus der Liste auswählen.',
            'publish_date.*' => 'Bitte angeben, wann die Veranstaltung online gehen soll.',
            'publish_time.*' => 'Bitte angeben, wann die Veranstaltung online gehen soll.',
            'organizer_name.required' => 'Bitte den Namen des Veranstalters eingeben.',
            'organizer_email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'organizer_website.url' => 'Bitte einen gültigen Link angeben.',
            ...$this->socialLinkMessages(),
            'ticket_url.url' => 'Bitte einen gültigen Link angeben.',
            'image.image' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
            'image.mimes' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
            'image.max' => 'Das Bild ist größer als '.(config('matrix.uploads.max_kilobytes') / 1024).' MB.',
            'image.dimensions' => 'Das Bild ist zu klein (mindestens 200 × 200 Pixel).',
            'image.uploaded' => 'Das Bild ist zu groß oder wurde nicht vollständig hochgeladen.',
            ...$this->uploadMessages('video'),
            'video_url.url' => 'Das ist kein gültiger YouTube-Link (z. B. https://www.youtube.com/watch?v=… oder https://youtu.be/…).',
            'genres.max' => 'Bitte höchstens 12 Genres wählen.',
            'genres.*' => 'Bitte nur Genres aus der Liste auswählen.',
            'new_genres.max' => 'Bitte höchstens 12 Genres wählen.',
            'new_genres.*.max' => 'Ein Genre darf höchstens 60 Zeichen lang sein.',
            'repeat_count.*' => 'Bitte 2 bis 52 Termine angeben.',
            'repeat_until.*' => 'Bitte ein gültiges Enddatum angeben.',
            'repeat_dates.*' => 'Bitte nur gültige Daten auswählen.',
            'featured_from_date.*' => 'Bitte angeben, ab wann das Highlight gilt.',
            'featured_from_time.*' => 'Bitte angeben, ab wann das Highlight gilt.',
            'featured_until_date.*' => 'Bitte angeben, bis wann das Highlight gilt.',
            'featured_until_time.*' => 'Bitte angeben, bis wann das Highlight gilt.',
        ];
    }

    /**
     * Spalten der Tabelle events aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function eventAttributes(): array
    {
        $startsAt = $this->startsAt();
        $category = $this->enum('category', EventCategoryEnum::class);
        $youtubeId = (new ExtractYoutubeIdAction)->execute($this->input('video_url'));

        return [
            'title' => $this->input('title'),
            'subtitle' => $this->input('subtitle'),
            'category' => $category,
            // Die freie Bezeichnung gibt es nur bei „Sonstiges“ – Musikrichtungen sind Genres
            'category_label' => $category === EventCategoryEnum::Sonstiges ? $this->input('category_label') : null,
            'description' => $this->input('description'),
            'starts_at' => $startsAt,
            'doors_at' => $this->input('doors'),
            'ends_at' => $this->endsAt($startsAt),
            'rooms' => $this->rooms(),
            'image_focus' => $this->enum('image_focus', ImageFocusEnum::class) ?? ImageFocusEnum::Center,
            'ticket_url' => $this->input('ticket_url'),
            'social_links' => $this->socialLinks() ?: null,
            'video_url' => $youtubeId !== null ? 'https://www.youtube.com/watch?v='.$youtubeId : null,
            'price_presale' => $this->input('price_presale'),
            'price_box' => $this->input('price_box'),
            'age_rating' => $this->input('age_rating'),
            'ticket_status' => $this->enum('ticket_status', TicketStatusEnum::class) ?? TicketStatusEnum::Available,
            'status' => $this->publication() === EventPublicationEnum::Draft ? EventStatusEnum::Draft : EventStatusEnum::Published,
            'publish_at' => $this->publishAt(),
            'is_featured' => $this->boolean('is_featured'),
            'featured_lead_days' => $this->boolean('is_featured') ? $this->highlightStart()->leadDays() : null,
            'featured_from' => $this->featuredFrom($startsAt),
            'featured_until' => $this->featuredUntil(),
        ];
    }

    /**
     * Ab wann das Highlight gilt – null = sofort.
     */
    private function featuredFrom(CarbonImmutable $startsAt): ?CarbonImmutable
    {
        if (! $this->boolean('is_featured')) {
            return null;
        }

        $start = $this->highlightStart();

        return match (true) {
            $start === HighlightStartEnum::Custom => CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('featured_from_date').' '.$this->input('featured_from_time')),
            $start->leadDays() !== null => $startsAt->subDays($start->leadDays()),
            default => null,
        };
    }

    /**
     * Bis wann das Highlight gilt – null = bis zur Veranstaltung.
     */
    private function featuredUntil(): ?CarbonImmutable
    {
        if (! $this->boolean('is_featured') || $this->enum('featured_end', HighlightEndEnum::class) !== HighlightEndEnum::Custom) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('featured_until_date').' '.$this->input('featured_until_time'));
    }

    private function highlightStart(): HighlightStartEnum
    {
        return $this->enum('featured_start', HighlightStartEnum::class) ?? HighlightStartEnum::Immediately;
    }

    /**
     * Angehakte Genres aus der Auswahl.
     *
     * @return list<int>
     */
    public function genreIds(): array
    {
        return array_map('intval', (array) $this->input('genres', []));
    }

    /**
     * Im Formular neu eingetippte Genres.
     *
     * @return list<string>
     */
    public function newGenreNames(): array
    {
        return array_values(array_filter(array_map('trim', (array) $this->input('new_genres', [])), 'filled'));
    }

    public function recurrence(): RecurrenceEnum
    {
        return $this->enum('repeat', RecurrenceEnum::class) ?? RecurrenceEnum::None;
    }

    public function recurrenceCount(): int
    {
        return (int) ($this->input('repeat_count') ?: 2);
    }

    /**
     * Letzter möglicher Termin bei Ende „Bis Datum“.
     */
    public function recurrenceUntil(): ?CarbonImmutable
    {
        return $this->enum('repeat_end', RecurrenceEndEnum::class) === RecurrenceEndEnum::Until && filled($this->input('repeat_until'))
            ? CarbonImmutable::createFromFormat('!Y-m-d', $this->input('repeat_until'))
            : null;
    }

    /**
     * Im Formular angehakte Folgetermine – null, wenn sie aus der Regel berechnet werden sollen (ohne JavaScript).
     *
     * @return list<string>|null
     */
    public function chosenRecurrenceDates(): ?array
    {
        if (! $this->boolean('repeat_js') && $this->recurrence() !== RecurrenceEnum::Custom) {
            return null;
        }

        return array_values(array_map('strval', (array) $this->input('repeat_dates', [])));
    }

    /**
     * Folgetermine, die zusätzlich angelegt werden.
     *
     * @return list<string> Daten im Format Y-m-d
     */
    public function seriesDates(): array
    {
        return app(ResolveSeriesDatesAction::class)->execute(
            $this->startsAt(),
            $this->recurrence(),
            $this->recurrenceCount(),
            $this->recurrenceUntil(),
            $this->chosenRecurrenceDates(),
        );
    }

    /**
     * Für welche Termine der Serie die Änderungen gelten.
     */
    public function seriesScope(): SeriesScopeEnum
    {
        return $this->enum('apply_to', SeriesScopeEnum::class) ?? SeriesScopeEnum::Single;
    }

    /**
     * Wurden Bild oder Video geändert? Dann übernehmen auch die anderen Serien-Termine die neuen Dateien.
     */
    public function changesMedia(): bool
    {
        return $this->hasFile('image') || $this->boolean('remove_image') || $this->hasFile('video') || $this->boolean('remove_video');
    }

    /**
     * Vorlage bei „Erneut ansetzen“.
     */
    public function template(): ?Event
    {
        return filled($this->input('template_id')) ? Event::query()->find($this->input('template_id')) : null;
    }

    /**
     * Der geplante Zeitpunkt muss in der Zukunft und vor Beginn der Veranstaltung liegen.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $highlightFields = ['date', 'time', 'featured_from_date', 'featured_from_time', 'featured_until_date', 'featured_until_time'];

                if (! $validator->errors()->hasAny($highlightFields) && $this->featuredUntil() !== null) {
                    $from = $this->featuredFrom($this->startsAt());

                    if ($this->featuredUntil()->lessThanOrEqualTo($from ?? now())) {
                        $validator->errors()->add('featured_until_date', 'Das Highlight-Ende muss nach dem Start liegen.');
                    }
                }
            },
            function (Validator $validator): void {
                if ($this->recurrence() === RecurrenceEnum::None || $validator->errors()->hasAny(['date', 'time', 'repeat', 'repeat_count', 'repeat_until', 'repeat_dates', 'repeat_dates.*'])) {
                    return;
                }

                if ($this->seriesDates() === []) {
                    $validator->errors()->add('repeat', 'Für die Wiederholung sind keine weiteren Termine ausgewählt.');
                }
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['date', 'time', 'publish_date', 'publish_time']) || $this->publication() !== EventPublicationEnum::Scheduled) {
                    return;
                }

                $publishAt = $this->publishAt();

                if ($publishAt->isPast()) {
                    $validator->errors()->add('publish_date', 'Der Zeitpunkt liegt schon in der Vergangenheit – dann einfach „Online“ wählen.');
                } elseif ($publishAt->greaterThanOrEqualTo($this->startsAt())) {
                    $validator->errors()->add('publish_date', 'Die Veranstaltung muss vor ihrem Beginn online gehen.');
                }
            },
        ];
    }

    private function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('date').' '.$this->input('time'));
    }

    public function publication(): ?EventPublicationEnum
    {
        return $this->enum('status', EventPublicationEnum::class);
    }

    /**
     * Zeitpunkt, ab dem eine geplante Veranstaltung auf der Website erscheint – null bei „Online“ und „Entwurf“.
     */
    public function publishAt(): ?CarbonImmutable
    {
        if ($this->publication() !== EventPublicationEnum::Scheduled) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('publish_date').' '.$this->input('publish_time'));
    }

    public function isExternal(): bool
    {
        return $this->input('organizer') === EventOrganizerEnum::External->value;
    }

    /**
     * Gewählter Veranstalter aus den Stammdaten – leer bei „Neuer Veranstalter“.
     */
    public function organizerId(): ?int
    {
        return $this->isExternal() && filled($this->input('organizer_id')) ? (int) $this->input('organizer_id') : null;
    }

    /**
     * Stammdaten des externen Veranstalters für die Tabelle organizers – null, wenn die Matrix veranstaltet.
     *
     * @return array<string, string|null>|null
     */
    public function organizerAttributes(): ?array
    {
        if (! $this->isExternal()) {
            return null;
        }

        $fields = ['name', 'contact_person', 'street', 'zip', 'city', 'phone', 'email', 'website'];

        return array_combine($fields, array_map(fn (string $field): ?string => $this->input('organizer_'.$field), $fields));
    }

    /**
     * Angehakte Räume in der Reihenfolge der Auswahl, ein frei eingegebener Ort hinten dran.
     *
     * @return list<string>|null
     */
    private function rooms(): ?array
    {
        $rooms = array_filter(RoomEnum::cases(), fn (RoomEnum $room): bool => in_array($room->value, (array) $this->input('rooms', []), true));
        $rooms = array_map(fn (RoomEnum $room): string => $room->value, $rooms);
        $custom = trim((string) $this->input('room_custom'));

        if ($custom !== '' && ! in_array($custom, $rooms, true)) {
            $rooms[] = $custom;
        }

        return $rooms === [] ? null : array_values($rooms);
    }

    /**
     * Endet die Veranstaltung „vor“ dem Beginn, ist der Folgetag gemeint (nach Mitternacht).
     */
    private function endsAt(CarbonImmutable $startsAt): ?CarbonImmutable
    {
        if (blank($this->input('end_time'))) {
            return null;
        }

        $endsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $startsAt->format('Y-m-d').' '.$this->input('end_time'));

        return $endsAt->lessThanOrEqualTo($startsAt) ? $endsAt->addDay() : $endsAt;
    }

    /**
     * Nach dem Speichern direkt eine weitere Veranstaltung anlegen?
     */
    public function wantsAnother(): bool
    {
        return Str::lower((string) $this->input('after')) === 'new';
    }
}
