<?php

namespace App\Http\Requests\Site;

use App\Models\JobPosition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ApplicationRequest extends SiteFormRequest
{
    /**
     * Auswahlwert für eine Bewerbung ohne bestimmte Stelle.
     */
    public const INITIATIVE = 'initiativ';

    protected function formAnchor(): string
    {
        return 'bewerben';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contactRules(requirePhone: true),
            'position' => ['required', Rule::when(
                $this->input('position') !== self::INITIATIVE,
                [Rule::exists(JobPosition::class, 'id')->where('is_active', true)],
            )],
            'start' => ['nullable', 'string', 'max:80'],
            'availability' => ['required', 'string', 'max:500'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:5000'],
            'is_adult' => ['accepted'],
            'attachment' => $this->attachmentRules(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'phone.required' => 'Bitte gib eine Telefonnummer an, damit wir dich erreichen.',
            'position.required' => 'Bitte wähle eine Stelle aus – oder „Initiativbewerbung“.',
            'position.exists' => 'Diese Stelle ist leider nicht mehr ausgeschrieben.',
            'availability.required' => 'Bitte gib an, wann du arbeiten kannst.',
            'is_adult.accepted' => 'Für die Jobs bei uns musst du mindestens 18 Jahre alt sein.',
            'attachment.mimes' => 'Bitte Unterlagen als PDF, Word-Datei, JPG oder PNG hochladen.',
        ];
    }

    /**
     * Titel der gewählten Stelle oder „Initiativbewerbung“.
     */
    public function positionTitle(): string
    {
        return $this->input('position') === self::INITIATIVE
            ? 'Initiativbewerbung'
            : JobPosition::query()->findOrFail($this->integer('position'))->title;
    }

    /**
     * @return array<string, mixed>
     */
    public function messageAttributes(): array
    {
        $position = $this->positionTitle();

        return [
            ...$this->contactAttributes(),
            'subject' => 'Bewerbung: '.$position,
            'body' => $this->input('body'),
            'details' => [
                'position' => $position,
                'start' => $this->input('start'),
                'availability' => $this->input('availability'),
                'experience' => $this->input('experience'),
                'is_adult' => $this->boolean('is_adult'),
            ],
        ];
    }
}
