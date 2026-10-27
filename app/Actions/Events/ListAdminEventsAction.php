<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventListFilterEnum;
use App\Enums\Events\EventStatusEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Veranstaltungsliste im Admin-Bereich mit Zeitraum, Art, Suche – oder alle Termine einer Serie.
 */
class ListAdminEventsAction
{
    /**
     * @return Collection<int, Event>
     */
    public function execute(EventListFilterEnum $filter, ?EventCategoryEnum $category = null, ?string $search = null, ?string $seriesId = null): Collection
    {
        $search = trim((string) $search);

        return Event::query()
            ->with(['media', 'genres'])
            ->withCount('seriesEvents')
            ->when($seriesId !== null, fn (Builder $query) => $query->where('series_id', $seriesId))
            ->when($seriesId === null && $filter === EventListFilterEnum::Upcoming, fn (Builder $query) => $query->upcoming())
            ->when($seriesId === null && $filter === EventListFilterEnum::Past, fn (Builder $query) => $query->past())
            ->when($seriesId === null && $filter === EventListFilterEnum::Drafts, fn (Builder $query) => $query->where('status', EventStatusEnum::Draft))
            ->when($seriesId === null && $filter === EventListFilterEnum::Scheduled, fn (Builder $query) => $query->scheduled())
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)
                    ->orWhere('category_label', 'like', $like)
                    ->orWhere('rooms', 'like', $like)
                    ->orWhereHas('genres', fn (Builder $query) => $query->where('name', 'like', $like)));
            })
            ->orderBy('starts_at', $filter === EventListFilterEnum::Past && $seriesId === null ? 'desc' : 'asc')
            ->get();
    }
}
