<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ReceiptEmployeeRequest extends AdminFormRequest
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
            'note' => ['nullable', 'string', 'max:150'],
            'if_unchanged_since' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'note' => 'Bereich / Notiz',
        ];
    }

    /**
     * Spalten der Tabelle receipt_employees aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function employeeAttributes(): array
    {
        return $this->only(['name', 'note']);
    }
}
