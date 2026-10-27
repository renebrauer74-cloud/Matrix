<?php

namespace App\Actions\Events;

use App\Enums\Events\RecurrenceEnum;
use Carbon\CarbonImmutable;

/**
 * Folgetermine einer Wiederholungsregel (ohne den ersten Termin).
 *
 * Dieselbe Rechnung steckt in computeDates() in public/assets/js/admin.js für die Vorschau im Formular.
 */
class CalculateRecurrenceDatesAction
{
    /**
     * Höchstzahl der Folgetermine einer Serie.
     */
    public const MAX_DATES = 104;

    /**
     * @param  int  $count  Termine insgesamt inklusive des ersten (bei Ende „Anzahl“)
     * @param  CarbonImmutable|null  $until  letzter möglicher Tag (bei Ende „Bis Datum“)
     * @return list<string> Daten im Format Y-m-d
     */
    public function execute(CarbonImmutable $start, RecurrenceEnum $rule, int $count, ?CarbonImmutable $until = null): array
    {
        if (! $rule->isRule()) {
            return [];
        }

        $start = $start->startOfDay();
        $until = $until?->startOfDay();
        $dates = [];

        for ($step = 1; $step <= self::MAX_DATES * 2 && count($dates) < self::MAX_DATES; $step++) {
            $date = $this->nth($start, $rule, $step);

            if ($date === null) {
                continue;
            }

            if ($until !== null ? $date->greaterThan($until) : count($dates) >= $count - 1) {
                break;
            }

            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Der n-te Folgetermin – null, wenn es ihn in diesem Monat nicht gibt (z. B. 31. im April).
     */
    private function nth(CarbonImmutable $start, RecurrenceEnum $rule, int $step): ?CarbonImmutable
    {
        return match ($rule) {
            RecurrenceEnum::Weekly => $start->addWeeks($step),
            RecurrenceEnum::Biweekly => $start->addWeeks($step * 2),
            RecurrenceEnum::MonthlyDate => $this->sameDate($start, $start->startOfMonth()->addMonthsNoOverflow($step)),
            RecurrenceEnum::MonthlyWeekday => $this->sameWeekday($start, $start->startOfMonth()->addMonthsNoOverflow($step)),
            default => null,
        };
    }

    private function sameDate(CarbonImmutable $start, CarbonImmutable $month): ?CarbonImmutable
    {
        return $start->day <= $month->daysInMonth ? $month->setDay($start->day) : null;
    }

    /**
     * Gleicher Wochentag an gleicher Stelle im Monat, z. B. „2. Samstag“ – war der Start der letzte
     * dieses Wochentags im Monat, dann immer der letzte.
     */
    private function sameWeekday(CarbonImmutable $start, CarbonImmutable $month): ?CarbonImmutable
    {
        if ($start->addWeek()->month !== $start->month) {
            return $month->lastOfMonth($start->dayOfWeek);
        }

        $date = $month->nthOfMonth((int) ceil($start->day / 7), $start->dayOfWeek);

        return $date === false ? null : $date;
    }
}
