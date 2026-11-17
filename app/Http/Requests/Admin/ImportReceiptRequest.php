<?php

namespace App\Http\Requests\Admin;

use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Eine Quittung aus der alten App-Version samt PDF und Unterschrift.
 */
class ImportReceiptRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:40'],
            'receipt_company_id' => ['nullable', 'integer', 'exists:receipt_companies,id'],
            'type' => ['required', Rule::enum(ReceiptTypeEnum::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'amount_cents' => ['required', 'integer', 'min:1', 'max:99999999'],
            'tax' => ['required', Rule::enum(ReceiptTaxEnum::class)],
            'partner_name' => ['required', 'string', 'max:150'],
            'partner_address' => ['nullable', 'string', 'max:1000'],
            'purpose' => ['required', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::enum(ReceiptPaymentMethodEnum::class)],
            'place' => ['required', 'string', 'max:100'],
            'signer_name' => ['nullable', 'string', 'max:150'],
            'signature' => ['nullable', 'string', 'starts_with:data:image/', 'max:3000000'],
            'signature_width' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'signature_height' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'company' => ['nullable', 'array'],
            'company.*' => ['nullable', 'string', 'max:190'],
            'created_at' => ['nullable', 'date'],
            'pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:'.config('matrix.uploads.max_kilobytes')],
        ];
    }
}
