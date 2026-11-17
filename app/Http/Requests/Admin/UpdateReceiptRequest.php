<?php

namespace App\Http\Requests\Admin;

use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Nur Kleinigkeiten – Art, Betrag, Datum, Firma und Empfänger sind unterschrieben und hier nicht änderbar.
 */
class UpdateReceiptRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'partner_address' => ['nullable', 'string', 'max:1000'],
            'purpose' => ['sometimes', 'required', 'string', 'max:2000'],
            'payment_method' => ['sometimes', 'required', Rule::enum(ReceiptPaymentMethodEnum::class)],
            'place' => ['sometimes', 'required', 'string', 'max:100'],
            'signer_name' => ['nullable', 'string', 'max:150'],
            'if_unchanged_since' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'partner_address' => 'Anschrift',
            'purpose' => 'Verwendungszweck',
            'payment_method' => 'Zahlungsart',
            'place' => 'Ort',
            'signer_name' => 'Name in Druckbuchstaben',
        ];
    }
}
