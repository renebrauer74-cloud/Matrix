<?php

namespace App\Enums\Events;

/**
 * Wiederholung einer Veranstaltung – jeder Termin wird als eigene Veranstaltung der Serie angelegt.
 */
enum RecurrenceEnum: string
{
    case None = 'none';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case MonthlyWeekday = 'monthly_weekday';
    case MonthlyDate = 'monthly_date';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Keine Wiederholung',
            self::Weekly => 'Jede Woche',
            self::Biweekly => 'Alle 2 Wochen',
            self::MonthlyWeekday => 'Jeden Monat am gleichen Wochentag',
            self::MonthlyDate => 'Jeden Monat am gleichen Datum',
            self::Custom => 'Eigene Termine auswählen',
        };
    }

    /**
     * Folgetermine ergeben sich aus einer Regel (statt frei gewählter Daten).
     */
    public function isRule(): bool
    {
        return ! in_array($this, [self::None, self::Custom], true);
    }
}
