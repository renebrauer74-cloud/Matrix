<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Firmen aus der alten App-Version, die nur auf einem Gerät gespeichert waren.
 */
class ImportReceiptCompaniesRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'companies' => ['present', 'array', 'max:50'],
            'companies.*.id' => ['required', 'string', 'max:100'],
            'companies.*.firma' => ['required', 'string', 'max:150'],
            'companies.*.strasse' => ['nullable', 'string', 'max:150'],
            'companies.*.plzOrt' => ['nullable', 'string', 'max:150'],
            'companies.*.steuernr' => ['nullable', 'string', 'max:40'],
            'companies.*.ustid' => ['nullable', 'string', 'max:40'],
            'companies.*.kontakt' => ['nullable', 'string', 'max:190'],
            'companies.*.kleinunternehmer' => ['nullable', 'boolean'],
            'companies.*.ort' => ['nullable', 'string', 'max:100'],
            'companies.*.standardUst' => ['nullable', 'string', 'max:4'],
            'companies.*.praefix' => ['required', 'string', 'max:10', 'regex:/^[\w-]+$/u'],
            'companies.*.zaehler' => ['nullable', 'array'],
            'companies.*.zaehler.*' => ['integer', 'min:0'],
        ];
    }
}
