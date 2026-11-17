<?php

namespace App\Actions\Receipts;

use App\Models\ReceiptCompany;

/**
 * Legt eine Firma an oder speichert sie – samt der nächsten laufenden Nummer für das aktuelle Jahr.
 */
class SaveReceiptCompanyAction
{
    /**
     * @param  array<string, mixed>  $attributes  Spalten aus ReceiptCompanyRequest::companyAttributes()
     * @param  int|null  $nextSequence  Nächste laufende Nummer in diesem Jahr (null = unverändert lassen)
     */
    public function execute(?ReceiptCompany $company, array $attributes, ?int $nextSequence): ReceiptCompany
    {
        $company ??= new ReceiptCompany;
        $company->fill($attributes);

        if ($nextSequence !== null && $nextSequence > 0) {
            $counters = $company->counters ?? [];
            $counters[now()->year] = $nextSequence - 1;
            $company->counters = $counters;
        }

        $company->save();

        return $company;
    }
}
