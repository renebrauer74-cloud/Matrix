<?php

namespace App\Actions\Events;

use App\Enums\Events\RecurrenceEnum;
use Carbon\CarbonImmutable;

/**
 * Welche Folgetermine angelegt werden: Mit JavaScript schickt das Formular die (abwählbare) Liste mit,
 * bei „Eigene Termine“ ohnehin – sonst werden sie aus der Regel berechnet.
 */
class ResolveSeriesDatesAction
{
    public function __construct(private CalculateRecurrenceDatesAction $calculateDates) {}

    /**
     * @param  list<string>|null  $chosenDates  im Formular gewählte Daten, null = aus der Regel berechnen
     * @return list<string> Daten im Format Y-m-d, aufsteigend, alle nach dem ersten Termin
     */
    public function execute(CarbonImmutable $start, RecurrenceEnum $rule, int $count, ?CarbonImmutable $until, ?array $chosenDates): array
    {
        if ($rule === RecurrenceEnum::None) {
            return [];
        }

        $dates = $chosenDates ?? $this->calculateDates->execute($start, $rule, $count, $until);
        $first = $start->startOfDay();
        $last = $first->addYears(2);

        $dates = array_filter(array_unique($dates), function (string $date) use ($first, $last): bool {
            $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

            return $day !== null && $day->format('Y-m-d') === $date && $day->greaterThan($first) && $day->lessThanOrEqualTo($last);
        });
        sort($dates);

        return array_slice($dates, 0, CalculateRecurrenceDatesAction::MAX_DATES);
    }
}
