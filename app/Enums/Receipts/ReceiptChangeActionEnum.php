<?php

namespace App\Enums\Receipts;

/**
 * Eintrag im Änderungsprotokoll einer Quittung.
 */
enum ReceiptChangeActionEnum: string
{
    case Created = 'created';
    case Imported = 'imported';
    case Updated = 'updated';
    case Booked = 'booked';
    case Unbooked = 'unbooked';
    case Cancelled = 'cancelled';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'erstellt',
            self::Imported => 'vom Gerät übernommen',
            self::Updated => 'geändert',
            self::Booked => 'als gebucht markiert',
            self::Unbooked => 'Markierung „gebucht“ entfernt',
            self::Cancelled => 'storniert',
            self::Deleted => 'gelöscht',
        };
    }
}
