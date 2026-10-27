<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Legt weitere Termine einer Veranstaltung an – jeweils als eigene Veranstaltung mit gleicher Uhrzeit,
 * gleichen Genres, Bild und Video. Alle Termine teilen sich eine series_id.
 */
class CreateEventSeriesAction
{
    public function __construct(
        private GenerateEventSlugAction $generateSlug,
        private CopyEventMediaAction $copyMedia,
    ) {}

    /**
     * @param  list<string>  $dates  Daten im Format Y-m-d
     * @return int Anzahl angelegter Termine
     */
    public function execute(Event $event, array $dates, ?User $author): int
    {
        if ($dates === []) {
            return 0;
        }

        $copies = DB::transaction(function () use ($event, $dates, $author): array {
            if (! $event->isSeries()) {
                $event->series_id = (string) Str::uuid();
                $event->saveQuietly();
            }

            $genreIds = $event->genres()->pluck('genres.id')->all();
            $firstDay = $event->starts_at->toImmutable()->startOfDay();
            $copies = [];

            foreach ($dates as $date) {
                $days = (int) round($firstDay->diffInDays(CarbonImmutable::createFromFormat('!Y-m-d', $date, $firstDay->timezone)));
                $copy = $event->replicate(['slug', 'created_at', 'updated_at']);

                foreach (['starts_at', 'ends_at', 'featured_from', 'featured_until'] as $column) {
                    $copy->{$column} = $event->{$column}?->copy()->addDays($days);
                }

                $copy->created_by = $author?->getKey();
                $copy->slug = $this->generateSlug->execute($copy);
                $copy->save();
                $copy->genres()->sync($genreIds);
                $copies[] = $copy;
            }

            return $copies;
        });

        foreach ($copies as $copy) {
            $this->copyMedia->execute($event, $copy);
        }

        return count($copies);
    }
}
