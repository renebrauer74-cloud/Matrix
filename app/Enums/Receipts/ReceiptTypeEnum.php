<?php

namespace App\Enums\Receipts;

/**
 * Art einer Quittung: Geld kommt herein, geht hinaus oder geht als Vorschuss an einen Mitarbeiter.
 */
enum ReceiptTypeEnum: string
{
    case Income = 'einnahme';
    case Expense = 'ausgabe';
    case Advance = 'vorschuss';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Einnahme',
            self::Expense => 'Ausgabe',
            self::Advance => 'Vorschuss',
        };
    }

    /**
     * Wir zahlen Geld aus (Ausgabe oder Vorschuss).
     */
    public function isPayout(): bool
    {
        return $this !== self::Income;
    }
}
