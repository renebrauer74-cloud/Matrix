<?php

namespace App\Actions\Events;

use App\Enums\Events\EventStatusEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kopiert eine Veranstaltung samt Genres, Bild und Video als Entwurf – Datum anpassen und veröffentlichen.
 * Die Kopie gehört zu keiner Serie.
 */
class DuplicateEventAction
{
    public function __construct(
        private GenerateEventSlugAction $generateSlug,
        private CopyEventMediaAction $copyMedia,
    ) {}

    public function execute(Event $event, ?User $author): Event
    {
        $copy = DB::transaction(function () use ($event, $author): Event {
            $copy = $event->replicate(['slug', 'created_at', 'updated_at']);
            $copy->title = $event->title.' (Kopie)';
            $copy->series_id = null;
            $copy->status = EventStatusEnum::Draft;
            $copy->publish_at = null;
            $copy->is_featured = false;
            $copy->featured_from = null;
            $copy->featured_until = null;
            $copy->featured_lead_days = null;
            $copy->created_by = $author?->getKey();
            $copy->slug = $this->generateSlug->execute($copy);
            $copy->save();
            $copy->genres()->sync($event->genres()->pluck('genres.id'));

            return $copy;
        });

        $this->copyMedia->execute($event, $copy);

        return $copy;
    }
}
