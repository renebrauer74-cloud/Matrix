<?php

namespace App\Enums\Events;

/**
 * Zeitraum-Reiter der Veranstaltungsliste im Admin-Bereich.
 */
enum EventListFilterEnum: string
{
    case Upcoming = 'upcoming';
    case Past = 'past';
    case Drafts = 'drafts';
    case Scheduled = 'scheduled';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Kommende',
            self::Past => 'Vergangene',
            self::Drafts => 'Entwürfe',
            self::Scheduled => 'Geplant',
            self::All => 'Alle',
        };
    }
}
