<?php

namespace App\Http\Requests\Admin;

use App\Enums\Receipts\ReceiptTaxEnum;
use App\Models\ReceiptCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ReceiptCompanyRequest extends AdminFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['prefix' => preg_replace('/[^\w-]/u', '', (string) $this->input('prefix'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $company = $this->route('receiptCompany');

        return [
            'name' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:150'],
            'zip_city' => ['required', 'string', 'max:150'],
            'tax_number' => ['nullable', 'string', 'max:40'],
            'vat_id' => ['nullable', 'string', 'max:40'],
            'contact' => ['nullable', 'string', 'max:190'],
            'is_small_business' => ['boolean'],
            'default_place' => ['nullable', 'string', 'max:100'],
            'default_tax' => ['required', Rule::enum(ReceiptTaxEnum::class)],
            'prefix' => [
                'required', 'string', 'max:10',
                Rule::unique('receipt_companies', 'prefix')->ignore($company instanceof ReceiptCompany ? $company->id : null),
            ],
            'next_sequence' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'if_unchanged_since' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prefix.unique' => 'Dieses Kürzel hat schon eine andere Firma. Jede Firma braucht ein eigenes Kürzel, sonst gibt es doppelte Quittungsnummern.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Firmenname',
            'street' => 'Straße, Nr.',
            'zip_city' => 'PLZ, Ort',
            'tax_number' => 'Steuernummer',
            'vat_id' => 'USt-IdNr.',
            'contact' => 'Kontakt',
            'default_place' => 'Standard-Ort',
            'default_tax' => 'Standard-USt',
            'prefix' => 'Kürzel',
            'next_sequence' => 'nächste laufende Nr.',
        ];
    }

    /**
     * Spalten der Tabelle receipt_companies aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function companyAttributes(): array
    {
        return [
            ...$this->only(['name', 'street', 'zip_city', 'tax_number', 'vat_id', 'contact', 'default_place', 'default_tax', 'prefix']),
            'is_small_business' => $this->boolean('is_small_business'),
        ];
    }
}
