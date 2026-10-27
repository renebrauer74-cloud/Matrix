<?php

namespace App\Enums\Events;

/**
 * Wie lange eine Wiederholung läuft: feste Anzahl Termine oder bis zu einem Datum.
 */
enum RecurrenceEndEnum: string
{
    case Count = 'count';
    case Until = 'until';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Anzahl',
            self::Until => 'Bis Datum',
        };
    }
}
