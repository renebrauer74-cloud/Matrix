<?php

namespace App\Enums\Events;

/**
 * Ab wann ein Highlight ganz vorne im Startseiten-Karussell steht.
 */
enum HighlightStartEnum: string
{
    case Immediately = 'now';
    case FourWeeksBefore = '28';
    case TwoWeeksBefore = '14';
    case OneWeekBefore = '7';
    case ThreeDaysBefore = '3';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Ab sofort',
            self::FourWeeksBefore => '4 Wochen vorher',
            self::TwoWeeksBefore => '2 Wochen vorher',
            self::OneWeekBefore => '1 Woche vorher',
            self::ThreeDaysBefore => '3 Tage vorher',
            self::Custom => 'Eigenes Datum …',
        };
    }

    /**
     * Tage vor Beginn der Veranstaltung – null bei „Ab sofort“ und eigenem Datum.
     */
    public function leadDays(): ?int
    {
        return ctype_digit($this->value) ? (int) $this->value : null;
    }

    public static function fromLeadDays(?int $days): ?self
    {
        return $days === null ? null : self::tryFrom((string) $days);
    }
}
