<?php

namespace App\Actions\Receipts;

/**
 * Quittungsnummer im Format Kürzel-Jahr-laufende Nummer, z. B. MX-2026-0015.
 */
class FormatReceiptNumberAction
{
    public function execute(string $prefix, int $year, int $sequence): string
    {
        return trim($prefix).'-'.$year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
