<?php

namespace App\Http\Requests\Site;

use App\Enums\Messages\CorporateEventTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class CorporateInquiryRequest extends SiteFormRequest
{
    protected function formAnchor(): string
    {
        return 'anfrage';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'company' => ['required', 'string', 'max:150'],
            'event_type' => ['required', Rule::enum(CorporateEventTypeEnum::class)],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'room' => ['nullable', 'string', 'max:80'],
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'company.required' => 'Bitte gib eure Firma an.',
            'event_type.required' => 'Bitte wähle einen Anlass aus.',
            'date.date_format' => 'Bitte gib ein gültiges Datum an.',
            'date.after_or_equal' => 'Der Wunschtermin liegt in der Vergangenheit.',
            'guests.integer' => 'Bitte gib die Gästezahl als Zahl an.',
            'guests.min' => 'Bitte gib die Gästezahl als Zahl an.',
            'guests.max' => 'So viele Gäste passen leider nicht in den Müser-Turm.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messageAttributes(): array
    {
        $eventType = $this->enum('event_type', CorporateEventTypeEnum::class);

        return [
            ...$this->contactAttributes(),
            'subject' => $eventType->label().' · '.$this->input('company'),
            'body' => $this->input('body'),
            'details' => [
                'company' => $this->input('company'),
                'event_type' => $eventType->value,
                'date' => $this->input('date'),
                'guests' => $this->filled('guests') ? $this->integer('guests') : null,
                'room' => $this->input('room'),
            ],
        ];
    }
}
