<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;

class LostItemRequest extends SiteFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'item' => ['required', 'string', 'max:150'],
            'lost_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:-1 year'],
            'room' => ['nullable', 'string', 'max:80'],
            'event' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'attachment' => $this->attachmentRules(['jpg', 'jpeg', 'png', 'webp', 'heic']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'item.required' => 'Bitte gib an, was du verloren hast.',
            'lost_on.required' => 'Bitte gib an, wann du da warst.',
            'lost_on.date_format' => 'Bitte gib ein gültiges Datum an.',
            'lost_on.before_or_equal' => 'Das Datum liegt in der Zukunft.',
            'lost_on.after' => 'Das Datum liegt mehr als ein Jahr zurück.',
            'body.required' => 'Bitte beschreib den Gegenstand kurz (Farbe, Marke, Besonderheiten).',
            'attachment.mimes' => 'Bitte ein Foto als JPG, PNG, WebP oder HEIC hochladen.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messageAttributes(): array
    {
        return [
            ...$this->contactAttributes(),
            'subject' => $this->input('item'),
            'body' => $this->input('body'),
            'details' => [
                'item' => $this->input('item'),
                'lost_on' => $this->input('lost_on'),
                'room' => $this->input('room'),
                'event' => $this->input('event'),
            ],
        ];
    }
}
