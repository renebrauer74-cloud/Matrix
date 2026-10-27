<?php

namespace App\Actions\Events;

use App\Enums\Events\SeriesScopeEnum;
use App\Models\Event;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;

/**
 * Löscht eine Veranstaltung – bei Serien auf Wunsch auch die folgenden oder alle Termine.
 * Genres, die danach keine Veranstaltung mehr hat, verschwinden aus der Auswahl.
 */
class DeleteEventAction
{
    /**
     * @return int Anzahl gelöschter Termine
     */
    public function execute(Event $event, SeriesScopeEnum $scope): int
    {
        $events = ! $event->isSeries() || $scope === SeriesScopeEnum::Single
            ? collect([$event])
            : Event::query()
                ->where('series_id', $event->series_id)
                ->when($scope === SeriesScopeEnum::Following, fn ($query) => $query->where('starts_at', '>=', $event->starts_at))
                ->get();

        DB::transaction(function () use ($events): void {
            $events->each(fn (Event $event) => $event->delete());
            Genre::query()->whereDoesntHave('events')->delete();
        });

        return $events->count();
    }
}
