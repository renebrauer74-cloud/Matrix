<?php

namespace App\Enums\Messages;

/**
 * Anlass in der Anfrage von Firmenkunden.
 */
enum CorporateEventTypeEnum: string
{
    case ChristmasParty = 'christmas';
    case SummerParty = 'summer';
    case Anniversary = 'anniversary';
    case ProductLaunch = 'launch';
    case Conference = 'conference';
    case TeamEvent = 'team';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ChristmasParty => 'Weihnachtsfeier',
            self::SummerParty => 'Sommerfest',
            self::Anniversary => 'Firmenjubiläum',
            self::ProductLaunch => 'Produktpräsentation',
            self::Conference => 'Tagung / Konferenz',
            self::TeamEvent => 'Teamevent',
            self::Other => 'Sonstiges',
        };
    }
}
