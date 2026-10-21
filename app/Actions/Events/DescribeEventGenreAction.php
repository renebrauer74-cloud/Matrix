<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Models\Event;

/**
 * Genre bzw. Motto bei Konzert und Party (optional) – bei „Sonstiges“ steht die Bezeichnung schon im Badge.
 */
class DescribeEventGenreAction
{
    public function execute(Event $event): ?string
    {
        if ($event->category === EventCategoryEnum::Sonstiges || blank($event->category_label)) {
            return null;
        }

        return $event->category_label;
    }
}
