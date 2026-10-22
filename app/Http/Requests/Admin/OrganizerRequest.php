<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class OrganizerRequest extends AdminFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $website = trim((string) $this->input('website'));

        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $this->merge(['website' => 'https://'.$website]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:150'],
            'zip' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:190'],
            'website' => ['nullable', 'url:http,https', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte den Namen des Veranstalters eingeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'website.url' => 'Bitte einen gültigen Link angeben.',
        ];
    }

    /**
     * Spalten der Tabelle organizers aus den Formularfeldern.
     *
     * @return array<string, string|null>
     */
    public function organizerAttributes(): array
    {
        return $this->only(['name', 'contact_person', 'street', 'zip', 'city', 'phone', 'email', 'website']);
    }
}
