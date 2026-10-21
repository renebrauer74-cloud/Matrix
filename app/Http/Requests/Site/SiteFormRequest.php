<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis für die Formulare der Website, deren Einsendungen im Admin-Posteingang landen.
 *
 * Das versteckte Feld „fax“ ist eine Spam-Falle: Menschen sehen es nicht, Bots füllen es aus.
 */
abstract class SiteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sprungmarke des Formulars auf der Seite – bei Fehlern geht es direkt dorthin zurück.
     */
    protected function formAnchor(): string
    {
        return 'formular';
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#'.$this->formAnchor();
    }

    /**
     * Spalten der Tabelle messages aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    abstract public function messageAttributes(): array;

    /**
     * Name, E-Mail, Telefon, Datenschutz-Häkchen und Spam-Falle – in allen Formularen gleich.
     *
     * @return array<string, list<string>>
     */
    protected function contactRules(bool $requirePhone = false): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => [$requirePhone ? 'required' : 'nullable', 'string', 'max:60'],
            'privacy' => ['accepted'],
            'fax' => ['prohibited'],
        ];
    }

    /**
     * Regeln für einen optionalen Anhang.
     *
     * @param  list<string>  $extensions
     * @return list<string>
     */
    protected function attachmentRules(array $extensions): array
    {
        return [
            'nullable',
            'file',
            'mimes:'.implode(',', $extensions),
            'max:'.config('matrix.uploads.attachment_max_kilobytes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMegabytes = config('matrix.uploads.attachment_max_kilobytes') / 1024;

        return [
            'name.required' => 'Bitte gib deinen Namen an.',
            'email.required' => 'Bitte gib deine E-Mail-Adresse an, damit wir antworten können.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse an.',
            'phone.required' => 'Bitte gib eine Telefonnummer an.',
            'privacy.accepted' => 'Bitte bestätige, dass du die Datenschutzhinweise gelesen hast.',
            'fax.prohibited' => 'Das Formular konnte nicht gesendet werden.',
            'attachment.max' => 'Die Datei ist größer als '.$maxMegabytes.' MB.',
            'attachment.uploaded' => 'Die Datei ist zu groß oder wurde nicht vollständig hochgeladen.',
            '*.max' => 'Der Text ist zu lang.',
        ];
    }

    /**
     * @return array{name: string, email: string, phone: ?string}
     */
    protected function contactAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'email' => trim((string) $this->input('email')),
            'phone' => $this->input('phone'),
        ];
    }
}
