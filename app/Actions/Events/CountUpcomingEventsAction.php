<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Models\Event;

/**
 * Anzahl kommender, veröffentlichter Veranstaltungen je Art – für Filter-Chips und Dashboard.
 */
class CountUpcomingEventsAction
{
    /**
     * @return array{all: int, konzert: int, party: int, sonstiges: int}
     */
    public function execute(): array
    {
        $byCategory = Event::query()
            ->published()
            ->upcoming()
            ->toBase()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $counts = ['all' => (int) $byCategory->sum()];

        foreach (EventCategoryEnum::cases() as $category) {
            $counts[$category->value] = (int) ($byCategory[$category->value] ?? 0);
        }

        return $counts;
    }
}
