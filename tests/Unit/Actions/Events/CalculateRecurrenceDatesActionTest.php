<?php

use App\Actions\Events\CalculateRecurrenceDatesAction;
use App\Enums\Events\RecurrenceEnum;
use Carbon\CarbonImmutable;

it('calculates the following dates of a recurrence rule', function (string $start, RecurrenceEnum $rule, int $count, ?string $until, array $expected) {
    $dates = (new CalculateRecurrenceDatesAction)->execute(
        CarbonImmutable::parse($start),
        $rule,
        $count,
        $until !== null ? CarbonImmutable::parse($until) : null,
    );

    expect($dates)->toBe($expected);
})->with([
    'weekly' => ['2026-10-31 23:00', RecurrenceEnum::Weekly, 4, null, ['2026-11-07', '2026-11-14', '2026-11-21']],
    'every two weeks' => ['2026-10-31', RecurrenceEnum::Biweekly, 3, null, ['2026-11-14', '2026-11-28']],
    'second saturday of the month' => ['2026-10-10', RecurrenceEnum::MonthlyWeekday, 4, null, ['2026-11-14', '2026-12-12', '2027-01-09']],
    'last saturday of the month' => ['2026-10-31', RecurrenceEnum::MonthlyWeekday, 3, null, ['2026-11-28', '2026-12-26']],
    'first friday of the month' => ['2026-10-02', RecurrenceEnum::MonthlyWeekday, 3, null, ['2026-11-06', '2026-12-04']],
    'skips months without the 31st' => ['2026-01-31', RecurrenceEnum::MonthlyDate, 4, null, ['2026-03-31', '2026-05-31', '2026-07-31']],
    'until a date' => ['2026-11-06', RecurrenceEnum::Weekly, 2, '2026-12-01', ['2026-11-13', '2026-11-20', '2026-11-27']],
    'across the change to summer time' => ['2026-03-22 22:00', RecurrenceEnum::Weekly, 3, null, ['2026-03-29', '2026-04-05']],
]);

it('returns no dates without a rule', function (RecurrenceEnum $rule) {
    expect((new CalculateRecurrenceDatesAction)->execute(CarbonImmutable::parse('2026-10-31'), $rule, 5))->toBe([]);
})->with([RecurrenceEnum::None, RecurrenceEnum::Custom]);
