<?php

namespace App\Enums\Events;

/**
 * Wer veranstaltet? Die Matrix selbst oder ein externer Veranstalter (dann mit Stammdaten in organizers).
 */
enum EventOrganizerEnum: string
{
    case Matrix = 'matrix';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Matrix => 'Matrix',
            self::External => 'Externer Veranstalter',
        };
    }
}
