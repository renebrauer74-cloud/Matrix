<?php

namespace App\Http\Requests\Admin;

use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAdvance = $this->input('type') === ReceiptTypeEnum::Advance->value;

        return [
            'receipt_company_id' => ['required', 'integer', 'exists:receipt_companies,id'],
            'type' => ['required', Rule::enum(ReceiptTypeEnum::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'amount_cents' => ['required', 'integer', 'min:1', 'max:99999999'],
            'tax' => ['required', Rule::enum(ReceiptTaxEnum::class)],
            'receipt_contact_id' => ['nullable', 'integer', 'exists:receipt_contacts,id'],
            'receipt_employee_id' => [$isAdvance ? 'required' : 'nullable', 'integer', 'exists:receipt_employees,id'],
            'partner_name' => [$isAdvance ? 'nullable' : 'required', 'string', 'max:150'],
            'partner_address' => ['nullable', 'string', 'max:1000'],
            'purpose' => ['required', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::enum(ReceiptPaymentMethodEnum::class)],
            'place' => ['required', 'string', 'max:100'],
            'signer_name' => ['nullable', 'string', 'max:150'],
            'signature' => ['required', 'string', 'starts_with:data:image/', 'max:3000000'],
            'signature_width' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'signature_height' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'corrects_receipt_id' => ['nullable', 'integer', 'exists:receipts,id'],
            'remember_contact' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt_employee_id.required' => 'Bitte den Mitarbeiter auswählen, der den Vorschuss bekommt.',
            'signature.required' => 'Bitte unterschreiben lassen.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'receipt_company_id' => 'Firma',
            'type' => 'Art',
            'date' => 'Datum',
            'amount_cents' => 'Betrag',
            'tax' => 'Umsatzsteuer',
            'receipt_contact_id' => 'Kontakt',
            'receipt_employee_id' => 'Mitarbeiter',
            'partner_name' => 'Name',
            'partner_address' => 'Anschrift',
            'purpose' => 'Verwendungszweck',
            'payment_method' => 'Zahlungsart',
            'place' => 'Ort',
            'signer_name' => 'Name in Druckbuchstaben',
            'signature' => 'Unterschrift',
        ];
    }
}
