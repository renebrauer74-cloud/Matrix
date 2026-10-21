<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AccountRequest extends AdminFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique(User::class)->ignore($this->user())],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Namen eingeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse eingeben.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
            'current_password.required_with' => 'Bitte das aktuelle Passwort eingeben.',
            'current_password.current_password' => 'Aktuelles Passwort ist falsch.',
            'new_password.min' => 'Mindestens 10 Zeichen.',
            'new_password.confirmed' => 'Passwörter stimmen nicht überein.',
        ];
    }
}
