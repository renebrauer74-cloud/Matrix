<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Kurzfassung der Beschreibung als reiner Text – für Meta-Description, Google und Kalender.
 */
class SummarizeEventDescriptionAction
{
    public function execute(Event $event, int $length = 160): string
    {
        $text = Str::of((string) $event->description)
            ->stripTags()
            ->replace('**', '')
            ->squish();

        return $text->length() > $length
            ? rtrim($text->substr(0, $length - 1)).'…'
            : $text->toString();
    }
}
