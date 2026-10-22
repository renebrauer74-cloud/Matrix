<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventListFilterEnum;
use App\Enums\Events\EventStatusEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Veranstaltungsliste im Admin-Bereich mit Zeitraum, Art und Suche.
 */
class ListAdminEventsAction
{
    /**
     * @return Collection<int, Event>
     */
    public function execute(EventListFilterEnum $filter, ?EventCategoryEnum $category = null, ?string $search = null): Collection
    {
        $search = trim((string) $search);

        return Event::query()
            ->with('media')
            ->when($filter === EventListFilterEnum::Upcoming, fn (Builder $query) => $query->upcoming())
            ->when($filter === EventListFilterEnum::Past, fn (Builder $query) => $query->past())
            ->when($filter === EventListFilterEnum::Drafts, fn (Builder $query) => $query->where('status', EventStatusEnum::Draft))
            ->when($filter === EventListFilterEnum::Scheduled, fn (Builder $query) => $query->scheduled())
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)
                    ->orWhere('category_label', 'like', $like)
                    ->orWhere('rooms', 'like', $like));
            })
            ->orderBy('starts_at', $filter === EventListFilterEnum::Past ? 'desc' : 'asc')
            ->get();
    }
}
