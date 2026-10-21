<?php

namespace App\Actions\Messages;

use App\Enums\Messages\ContactTopicEnum;
use App\Enums\Messages\CorporateEventTypeEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;
use Carbon\CarbonImmutable;

/**
 * Die formularspezifischen Angaben einer Nachricht als „Bezeichnung => Wert“ für die Detailansicht.
 */
class DescribeMessageDetailsAction
{
    /**
     * @return array<string, string>
     */
    public function execute(Message $message): array
    {
        $details = $message->details ?? [];
        $value = fn (string $key): ?string => filled($details[$key] ?? null) ? (string) $details[$key] : null;

        $rows = match ($message->type) {
            MessageTypeEnum::Contact => [
                'Thema' => ContactTopicEnum::tryFrom((string) $value('topic'))?->label(),
            ],
            MessageTypeEnum::LostItem => [
                'Gegenstand' => $value('item'),
                'Verloren am' => $this->formatDate($value('lost_on')),
                'Raum' => $value('room'),
                'Veranstaltung' => $value('event'),
            ],
            MessageTypeEnum::Application => [
                'Stelle' => $value('position'),
                'Ab wann' => $value('start'),
                'Verfügbarkeit' => $value('availability'),
                'Erfahrung' => $value('experience'),
                'Volljährig' => ($details['is_adult'] ?? false) ? 'Ja' : null,
            ],
            MessageTypeEnum::CorporateInquiry => [
                'Firma' => $value('company'),
                'Anlass' => CorporateEventTypeEnum::tryFrom((string) $value('event_type'))?->label(),
                'Wunschtermin' => $this->formatDate($value('date')),
                'Gäste' => $value('guests') !== null ? 'ca. '.$value('guests') : null,
                'Raum' => $value('room'),
            ],
        };

        return array_filter($rows, fn (?string $row): bool => filled($row));
    }

    private function formatDate(?string $date): ?string
    {
        return $date !== null ? de_date_short(CarbonImmutable::parse($date)) : null;
    }
}
