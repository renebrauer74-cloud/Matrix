<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaCollectionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Mehrere Bilder für die Galerie oder Videos für die Videoliste einer gepflegten Seite.
 */
class PageMediaRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $collection = MediaCollectionEnum::tryFrom((string) $this->input('collection'));

        return [
            'collection' => ['required', Rule::in([MediaCollectionEnum::PageGallery->value, MediaCollectionEnum::PageVideos->value])],
            'files' => ['required', 'array', 'max:30'],
            'files.*' => $collection?->holdsVideos() ? $this->videoRules(required: true) : $this->imageRules(required: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Bitte mindestens eine Datei auswählen.',
            'files.max' => 'Bitte höchstens 30 Dateien auf einmal hochladen.',
            ...$this->uploadMessages('files.*'),
        ];
    }

    public function collection(): MediaCollectionEnum
    {
        return $this->enum('collection', MediaCollectionEnum::class);
    }
}
