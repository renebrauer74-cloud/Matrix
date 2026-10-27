<?php

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Zeitraum eines Highlights für den Admin, z. B. „Highlight ab 13.11. 20:00 bis zur Veranstaltung“.
 */
class DescribeHighlightWindowAction
{
    public function execute(Event $event): ?string
    {
        if (! $event->is_featured) {
            return null;
        }

        $from = $event->featured_from ? 'ab '.$event->featured_from->format('d.m. H:i') : 'ab sofort';
        $until = $event->featured_until ? 'bis '.$event->featured_until->format('d.m. H:i') : 'bis zur Veranstaltung';

        return 'Highlight '.$from.' '.$until;
    }
}
