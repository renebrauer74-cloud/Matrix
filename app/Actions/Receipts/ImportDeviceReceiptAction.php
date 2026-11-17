<?php

namespace App\Actions\Receipts;

use App\Enums\MediaCollectionEnum;
use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\ReceiptCompany;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt eine Quittung, die die alte App-Version nur auf einem Gerät gespeichert hat – samt PDF und Unterschrift.
 *
 * Gibt es die Nummer schon, wird die Quittung übersprungen (null).
 */
class ImportDeviceReceiptAction
{
    public function __construct(private LogReceiptChangeAction $logChange) {}

    /**
     * @param  array<string, mixed>  $data  validierte Felder aus ImportReceiptRequest
     */
    public function execute(array $data, ?UploadedFile $pdf, ?string $signature, User $user): ?Receipt
    {
        if (Receipt::withTrashed()->where('number', $data['number'])->exists()) {
            return null;
        }

        return DB::transaction(function () use ($data, $pdf, $signature, $user): Receipt {
            $company = isset($data['receipt_company_id']) ? ReceiptCompany::query()->find($data['receipt_company_id']) : null;
            $snapshot = (array) ($data['company'] ?? []);
            $createdAt = isset($data['created_at']) ? Carbon::parse($data['created_at'])->setTimezone(config('app.timezone')) : now();

            $receipt = Receipt::query()->create([
                'receipt_company_id' => $company?->id,
                'number' => $data['number'],
                'type' => $data['type'],
                'date' => $data['date'],
                'amount_cents' => (int) $data['amount_cents'],
                'tax' => $data['tax'],
                'partner_name' => $data['partner_name'],
                'partner_address' => $data['partner_address'] ?? null,
                'purpose' => $data['purpose'],
                'payment_method' => $data['payment_method'],
                'place' => $data['place'],
                'signer_name' => $data['signer_name'] ?? null,
                'signature_width' => $data['signature_width'] ?? null,
                'signature_height' => $data['signature_height'] ?? null,
                'company_snapshot' => [
                    'name' => (string) ($snapshot['firma'] ?? $company?->name ?? ''),
                    'street' => (string) ($snapshot['strasse'] ?? $company?->street ?? ''),
                    'zip_city' => (string) ($snapshot['plzOrt'] ?? $company?->zip_city ?? ''),
                    'tax_number' => $snapshot['steuernr'] ?? $company?->tax_number,
                    'vat_id' => $snapshot['ustid'] ?? $company?->vat_id,
                    'contact' => $snapshot['kontakt'] ?? $company?->contact,
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => $createdAt,
            ]);

            if ($company !== null && preg_match('/^'.preg_quote($company->prefix, '/').'-(\d{4})-(\d+)$/i', $receipt->number, $match)) {
                $counters = $company->counters ?? [];
                $counters[(int) $match[1]] = max((int) ($counters[(int) $match[1]] ?? 0), (int) $match[2]);
                $company->update(['counters' => $counters]);
            }

            if ($pdf !== null) {
                $receipt->addMedia($pdf)->toMediaCollection(MediaCollectionEnum::ReceiptPdf->value);
            }

            if (filled($signature)) {
                $receipt->addMediaFromBase64($signature, 'image/jpeg', 'image/png')
                    ->usingFileName('unterschrift-'.$receipt->id.'.jpg')
                    ->toMediaCollection(MediaCollectionEnum::ReceiptSignature->value);
            }

            $this->logChange->execute($receipt, ReceiptChangeActionEnum::Imported, $user);

            return $receipt;
        });
    }
}
