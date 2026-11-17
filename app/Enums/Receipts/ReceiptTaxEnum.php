<?php

namespace App\Enums\Receipts;

/**
 * Umsatzsteuer im Bruttobetrag einer Quittung.
 */
enum ReceiptTaxEnum: string
{
    case Standard = '19';
    case Reduced = '7';
    case None = '0';
    case SmallBusiness = 'ku';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'inkl. 19 % USt',
            self::Reduced => 'inkl. 7 % USt',
            self::None => 'ohne USt',
            self::SmallBusiness => 'Kleinunternehmer (§ 19 UStG)',
        };
    }
}
