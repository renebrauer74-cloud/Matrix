<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Models\Event;

/**
 * Text für das Art-Badge: bei „Sonstiges“ die freie Bezeichnung (z. B. „Lesung“), sonst die Art.
 */
class DescribeEventBadgeAction
{
    public function execute(Event $event): string
    {
        if ($event->category === EventCategoryEnum::Sonstiges && filled($event->category_label)) {
            return $event->category_label;
        }

        return $event->category->label();
    }
}
