<?php

namespace App\Actions\Receipts;

use App\Enums\MediaCollectionEnum;
use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use App\Models\Receipt;
use App\Models\ReceiptCompany;
use App\Models\ReceiptEmployee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stellt eine Quittung aus: Nummer vergeben, Firmendaten festhalten, Unterschrift speichern, Kontakt merken.
 *
 * Das PDF baut danach die App mit der vergebenen Nummer und lädt es hoch.
 */
class CreateReceiptAction
{
    public function __construct(
        private AllocateReceiptNumberAction $allocateNumber,
        private RememberReceiptContactAction $rememberContact,
        private DescribeReceiptCompanyAction $describeCompany,
        private LogReceiptChangeAction $logChange,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validierte Felder aus StoreReceiptRequest
     */
    public function execute(array $data, string $signature, User $user): Receipt
    {
        return DB::transaction(function () use ($data, $signature, $user): Receipt {
            $company = ReceiptCompany::query()->findOrFail($data['receipt_company_id']);
            $type = ReceiptTypeEnum::from($data['type']);
            $date = Carbon::parse($data['date']);

            $attributes = [
                'receipt_company_id' => $company->id,
                'type' => $type,
                'date' => $date,
                'amount_cents' => (int) $data['amount_cents'],
                'tax' => $data['tax'],
                'receipt_contact_id' => null,
                'receipt_employee_id' => null,
                'partner_name' => trim((string) ($data['partner_name'] ?? '')),
                'partner_address' => $data['partner_address'] ?? null,
                'purpose' => $data['purpose'],
                'payment_method' => $data['payment_method'],
                'place' => $data['place'],
                'signer_name' => $data['signer_name'] ?? null,
                'signature_width' => $data['signature_width'] ?? null,
                'signature_height' => $data['signature_height'] ?? null,
                'corrects_receipt_id' => $data['corrects_receipt_id'] ?? null,
                'company_snapshot' => $this->describeCompany->execute($company),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            if ($type === ReceiptTypeEnum::Advance) {
                $employee = ReceiptEmployee::query()->findOrFail($data['receipt_employee_id']);
                $attributes['receipt_employee_id'] = $employee->id;
                $attributes['partner_name'] = $employee->name;
                $attributes['tax'] = ReceiptTaxEnum::None;
            } elseif (! empty($data['remember_contact'])) {
                $contact = $this->rememberContact->execute(
                    isset($data['receipt_contact_id']) ? (int) $data['receipt_contact_id'] : null,
                    $attributes['partner_name'],
                    $attributes['partner_address'],
                );
                $attributes['receipt_contact_id'] = $contact->id;
            } elseif (isset($data['receipt_contact_id'])) {
                $attributes['receipt_contact_id'] = (int) $data['receipt_contact_id'];
            }

            $attributes['number'] = $this->allocateNumber->execute($company, $date->year);

            $receipt = Receipt::query()->create($attributes);

            $receipt->addMediaFromBase64($signature, 'image/jpeg', 'image/png')
                ->usingFileName('unterschrift-'.$receipt->id.'.jpg')
                ->toMediaCollection(MediaCollectionEnum::ReceiptSignature->value);

            $this->logChange->execute($receipt, ReceiptChangeActionEnum::Created, $user);

            return $receipt;
        });
    }
}
