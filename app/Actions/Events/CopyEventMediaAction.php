<?php

namespace App\Actions\Events;

use App\Enums\MediaCollectionEnum;
use App\Models\Event;

/**
 * Übernimmt Bild und Video einer Veranstaltung für eine andere (Serien-Termin, „Erneut ansetzen“).
 */
class CopyEventMediaAction
{
    public function execute(Event $from, Event $to): void
    {
        foreach ([MediaCollectionEnum::Events, MediaCollectionEnum::EventVideo] as $collection) {
            $media = $from->getFirstMedia($collection->value);

            if ($media === null) {
                $to->clearMediaCollection($collection->value);

                continue;
            }

            $media->copy($to, $collection->value);
        }
    }
}
