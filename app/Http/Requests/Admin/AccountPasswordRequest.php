<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class AccountPasswordRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Bitte das aktuelle Passwort eingeben.',
            'current_password.current_password' => 'Aktuelles Passwort ist falsch.',
            'password.required' => 'Bitte ein neues Passwort eingeben.',
            'password.min' => 'Mindestens 10 Zeichen.',
            'password.confirmed' => 'Die beiden neuen Passwörter stimmen nicht überein.',
            'password.different' => 'Das neue Passwort muss sich vom aktuellen unterscheiden.',
        ];
    }
}
