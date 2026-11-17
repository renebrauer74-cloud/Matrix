<?php

namespace App\Actions\Receipts;

use App\Http\Resources\ReceiptCompanyResource;
use App\Http\Resources\ReceiptContactResource;
use App\Http\Resources\ReceiptEmployeeResource;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use App\Models\ReceiptChange;
use App\Models\ReceiptCompany;
use App\Models\ReceiptContact;
use App\Models\ReceiptEmployee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Gesamter Stand der Quittungs-App (Firmen, Kontakte, Mitarbeiter, Quittungen) für den Abgleich zwischen Geräten.
 *
 * Kennt das Gerät den aktuellen Stand schon (gleiche Version), kommt nur „unverändert“ zurück.
 */
class BuildReceiptSyncStateAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(?string $knownVersion, Request $request): array
    {
        $version = $this->version();

        if ($knownVersion === $version) {
            return ['version' => $version, 'unchanged' => true];
        }

        $receipts = Receipt::query()
            ->with(ReceiptResource::RELATIONS)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return [
            'version' => $version,
            'unchanged' => false,
            'companies' => ReceiptCompanyResource::collection(ReceiptCompany::query()->orderBy('id')->get())->resolve($request),
            'contacts' => ReceiptContactResource::collection(ReceiptContact::query()->with('media')->orderBy('name')->get())->resolve($request),
            'employees' => ReceiptEmployeeResource::collection(ReceiptEmployee::query()->orderBy('name')->get())->resolve($request),
            'receipts' => ReceiptResource::collection($receipts)->resolve($request),
        ];
    }

    /**
     * Fingerabdruck aller Tabellen – ändert sich bei jedem Anlegen, Ändern und Löschen.
     */
    private function version(): string
    {
        $fingerprint = fn (Builder $query, string ...$columns): array => (array) $query
            ->selectRaw('count(*) as total'.implode('', array_map(fn (string $column): string => ', max('.$column.') as max_'.$column, $columns)))
            ->toBase()
            ->first();

        return md5((string) json_encode([
            $fingerprint(ReceiptCompany::query(), 'updated_at'),
            $fingerprint(ReceiptContact::query(), 'updated_at'),
            $fingerprint(ReceiptEmployee::query(), 'updated_at'),
            $fingerprint(Receipt::withTrashed(), 'updated_at', 'deleted_at'),
            $fingerprint(ReceiptChange::query(), 'id'),
        ]));
    }
}
