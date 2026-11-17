<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptTaxEnum;
use App\Models\ReceiptCompany;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt die Firmen, die die alte App-Version nur auf einem Gerät gespeichert hat.
 *
 * Gibt es schon eine Firma mit demselben Kürzel, wird sie verwendet und nur der Nummernkreis nachgezogen.
 */
class ImportDeviceCompaniesAction
{
    /**
     * @param  list<array<string, mixed>>  $companies  Firmen im Format der alten App (firma, strasse, plzOrt, praefix, zaehler …)
     * @return array<string, int> Kennung auf dem Gerät => ID der Firma auf dem Server
     */
    public function execute(array $companies): array
    {
        return DB::transaction(function () use ($companies): array {
            $map = [];

            foreach ($companies as $device) {
                $prefix = (string) $device['praefix'];
                $company = ReceiptCompany::query()->whereRaw('UPPER(prefix) = ?', [mb_strtoupper($prefix)])->first();

                $counters = $company?->counters ?? [];
                foreach ((array) ($device['zaehler'] ?? []) as $year => $sequence) {
                    $counters[(int) $year] = max((int) ($counters[(int) $year] ?? 0), (int) $sequence);
                }

                if ($company === null) {
                    $company = ReceiptCompany::query()->create([
                        'name' => $device['firma'],
                        'street' => $device['strasse'] ?? '',
                        'zip_city' => $device['plzOrt'] ?? '',
                        'tax_number' => $device['steuernr'] ?? null,
                        'vat_id' => $device['ustid'] ?? null,
                        'contact' => $device['kontakt'] ?? null,
                        'is_small_business' => (bool) ($device['kleinunternehmer'] ?? false),
                        'default_place' => $device['ort'] ?? null,
                        'default_tax' => ReceiptTaxEnum::tryFrom((string) ($device['standardUst'] ?? '')) ?? ReceiptTaxEnum::Standard,
                        'prefix' => $prefix,
                        'counters' => $counters,
                    ]);
                } else {
                    $company->update(['counters' => $counters]);
                }

                $map[(string) $device['id']] = $company->id;
            }

            return $map;
        });
    }
}
