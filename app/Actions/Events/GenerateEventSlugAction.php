<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Eindeutiger Slug aus Titel und Datum, z. B. „neon-nights-2026-10-25“ (bei Gleichstand „…-2“).
 */
class GenerateEventSlugAction
{
    public function execute(Event $event): string
    {
        $title = Str::limit(Str::slug($event->title, '-', 'de', ['&' => 'und', '@' => 'at']), 180, '');
        $base = ltrim(rtrim($title, '-').'-'.$event->starts_at->format('Y-m-d'), '-');
        $slug = $base;

        for ($suffix = 2; $this->isTaken($slug, $event); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    private function isTaken(string $slug, Event $event): bool
    {
        return Event::query()
            ->where('slug', $slug)
            ->when($event->exists, fn ($query) => $query->whereKeyNot($event->getKey()))
            ->exists();
    }
}
