<?php

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Genres einer Veranstaltung als Kurztext für Karten und Listen, z. B. „Metal · Punk +1“.
 */
class DescribeEventGenreAction
{
    public function execute(Event $event, int $limit = 2): ?string
    {
        $names = $event->genres->pluck('name');

        if ($names->isEmpty()) {
            return null;
        }

        $text = $names->take($limit)->implode(' · ');

        return $names->count() > $limit ? $text.' +'.($names->count() - $limit) : $text;
    }
}
