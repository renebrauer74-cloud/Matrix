<?php

namespace App\Http\Requests\Admin;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\ImageFocusEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\Location\RoomEnum;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'image' => $this->imageRules(),
            'remove_image' => ['nullable', 'boolean'],
            'image_focus' => ['nullable', Rule::enum(ImageFocusEnum::class)],
            'ticket_url' => ['nullable', 'url', 'max:500'],
            'price_presale' => ['nullable', 'string', 'max:60'],
            'price_box' => ['nullable', 'string', 'max:60'],
            'age_rating' => ['nullable', 'string', 'max:40'],
            'ticket_status' => ['nullable', Rule::enum(TicketStatusEnum::class)],
            'status' => ['required', Rule::enum(EventStatusEnum::class)],
            'is_featured' => ['nullable', 'boolean'],
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
            'ticket_url.url' => 'Bitte einen gültigen Link angeben.',
            'image.image' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
            'image.mimes' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
            'image.max' => 'Das Bild ist größer als '.(config('matrix.uploads.max_kilobytes') / 1024).' MB.',
            'image.dimensions' => 'Das Bild ist zu klein (mindestens 200 × 200 Pixel).',
            'image.uploaded' => 'Das Bild ist zu groß oder wurde nicht vollständig hochgeladen.',
        ];
    }

    /**
     * Spalten der Tabelle events aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function eventAttributes(): array
    {
        $startsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('date').' '.$this->input('time'));

        return [
            'title' => $this->input('title'),
            'subtitle' => $this->input('subtitle'),
            'category' => $this->enum('category', EventCategoryEnum::class),
            'category_label' => $this->input('category_label'),
            'description' => $this->input('description'),
            'starts_at' => $startsAt,
            'doors_at' => $this->input('doors'),
            'ends_at' => $this->endsAt($startsAt),
            'rooms' => $this->rooms(),
            'image_focus' => $this->enum('image_focus', ImageFocusEnum::class) ?? ImageFocusEnum::Center,
            'ticket_url' => $this->input('ticket_url'),
            'price_presale' => $this->input('price_presale'),
            'price_box' => $this->input('price_box'),
            'age_rating' => $this->input('age_rating'),
            'ticket_status' => $this->enum('ticket_status', TicketStatusEnum::class) ?? TicketStatusEnum::Available,
            'status' => $this->enum('status', EventStatusEnum::class),
            'is_featured' => $this->boolean('is_featured'),
        ];
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
