<?php

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Preiszeile, z. B. „VVK 24 € · AK 29 €“ – null, wenn keine Preise eingetragen sind.
 */
class DescribeEventPriceAction
{
    public function execute(Event $event): ?string
    {
        $parts = array_filter([
            filled($event->price_presale) ? 'VVK '.$event->price_presale : null,
            filled($event->price_box) ? 'AK '.$event->price_box : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
