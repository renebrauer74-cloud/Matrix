<?php

namespace App\Actions\Events;

use App\Enums\Events\SeriesScopeEnum;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Überträgt die Angaben eines Serien-Termins auf die folgenden oder alle anderen Termine.
 *
 * Jeder Termin behält sein Datum, seinen Ticket-Status, sein Highlight und seine geplante Veröffentlichung.
 */
class ApplyEventToSeriesAction
{
    /**
     * Spalten, die je Termin eigenständig bleiben.
     */
    private const PER_DATE = [
        'series_id', 'slug', 'starts_at', 'ends_at', 'ticket_status', 'publish_at',
        'is_featured', 'featured_from', 'featured_until', 'featured_lead_days', 'created_by',
    ];

    public function __construct(
        private GenerateEventSlugAction $generateSlug,
        private CopyEventMediaAction $copyMedia,
    ) {}

    /**
     * @param  bool  $withMedia  Bild/Video wurden geändert und sollen ebenfalls übernommen werden
     * @return int Anzahl geänderter weiterer Termine
     */
    public function execute(Event $event, SeriesScopeEnum $scope, bool $withMedia): int
    {
        if (! $event->isSeries() || $scope === SeriesScopeEnum::Single) {
            return 0;
        }

        $others = Event::query()
            ->where('series_id', $event->series_id)
            ->whereKeyNot($event->getKey())
            ->when($scope === SeriesScopeEnum::Following, fn ($query) => $query->where('starts_at', '>', $event->starts_at))
            ->get();

        $shared = collect($event->only($event->getFillable()))->except(self::PER_DATE)->all();
        $genreIds = $event->genres()->pluck('genres.id')->all();
        $duration = $event->ends_at ? $event->starts_at->diffInMinutes($event->ends_at) : null;

        DB::transaction(function () use ($others, $event, $shared, $genreIds, $duration): void {
            foreach ($others as $other) {
                $other->fill($shared);
                $other->starts_at = $other->starts_at->copy()->setTimeFrom($event->starts_at);
                $other->ends_at = $duration !== null ? $other->starts_at->copy()->addMinutes($duration) : null;
                $other->slug = $this->generateSlug->execute($other);
                $other->save();
                $other->genres()->sync($genreIds);
            }
        });

        if ($withMedia) {
            $others->each(fn (Event $other) => $this->copyMedia->execute($event, $other));
        }

        return $others->count();
    }
}
