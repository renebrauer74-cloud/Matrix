<?php

namespace App\Actions\Receipts;

use App\Models\Receipt;
use App\Models\ReceiptCompany;
use Illuminate\Support\Facades\DB;

/**
 * Vergibt die nächste freie Quittungsnummer einer Firma – gesperrt, damit zwei Geräte nie dieselbe Nummer bekommen.
 */
class AllocateReceiptNumberAction
{
    public function __construct(private FormatReceiptNumberAction $formatNumber) {}

    public function execute(ReceiptCompany $company, int $year): string
    {
        return DB::transaction(function () use ($company, $year): string {
            $company = ReceiptCompany::query()->lockForUpdate()->findOrFail($company->id);
            $counters = $company->counters ?? [];
            $sequence = (int) ($counters[$year] ?? 0) + 1;

            while (Receipt::withTrashed()->where('number', $this->formatNumber->execute($company->prefix, $year, $sequence))->exists()) {
                $sequence++;
            }

            $counters[$year] = $sequence;
            $company->update(['counters' => $counters]);

            return $this->formatNumber->execute($company->prefix, $year, $sequence);
        });
    }
}
