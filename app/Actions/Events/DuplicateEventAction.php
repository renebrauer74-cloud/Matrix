<?php

namespace App\Actions\Events;

use App\Enums\Events\EventStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kopiert eine Veranstaltung samt Bild als Entwurf – Datum anpassen und veröffentlichen.
 */
class DuplicateEventAction
{
    public function __construct(private GenerateEventSlugAction $generateSlug) {}

    public function execute(Event $event, ?User $author): Event
    {
        return DB::transaction(function () use ($event, $author): Event {
            $copy = $event->replicate(['slug', 'created_at', 'updated_at']);
            $copy->title = $event->title.' (Kopie)';
            $copy->status = EventStatusEnum::Draft;
            $copy->is_featured = false;
            $copy->created_by = $author?->getKey();
            $copy->slug = $this->generateSlug->execute($copy);
            $copy->save();

            $event->getFirstMedia(MediaCollectionEnum::Events->value)
                ?->copy($copy, MediaCollectionEnum::Events->value);

            return $copy;
        });
    }
}
