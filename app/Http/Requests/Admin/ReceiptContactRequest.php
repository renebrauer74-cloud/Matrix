<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ReceiptContactRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'is_dj' => ['boolean'],
            'real_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:190'],
            'technical_rider' => ['nullable', 'string', 'max:5000'],
            'hospitality_rider' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'if_unchanged_since' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name bzw. Künstlername',
            'real_name' => 'bürgerlicher Name',
            'address' => 'Anschrift',
            'phone' => 'Telefon',
            'email' => 'E-Mail',
            'technical_rider' => 'Technik-Rider',
            'hospitality_rider' => 'Hospitality-Rider',
            'notes' => 'Notizen',
        ];
    }

    /**
     * Spalten der Tabelle receipt_contacts aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function contactAttributes(): array
    {
        return [
            ...$this->only(['name', 'real_name', 'address', 'phone', 'email', 'technical_rider', 'hospitality_rider', 'notes']),
            'is_dj' => $this->boolean('is_dj'),
        ];
    }
}
