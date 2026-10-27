<?php

namespace App\Enums\Events;

/**
 * Bis wann ein Highlight ganz vorne im Startseiten-Karussell steht.
 */
enum HighlightEndEnum: string
{
    case EventStart = 'event';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::EventStart => 'Bis zur Veranstaltung',
            self::Custom => 'Eigenes Datum …',
        };
    }
}
