<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class RoomRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:2000'],
            'size' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'bars' => ['required', 'integer', 'min:0', 'max:50'],
            'stage' => ['nullable', 'string', 'max:80'],
            'extra' => ['nullable', 'string', 'max:80'],
            'image' => $this->imageRules(),
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'capacity.*' => 'Bitte eine Personenzahl angeben, z. B. 700.',
            'bars.*' => 'Bitte die Anzahl der Theken als Zahl angeben (0 = keine).',
            ...$this->uploadMessages('image'),
        ];
    }

    /**
     * Spalten der Tabelle rooms aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function roomAttributes(): array
    {
        return [
            'description' => $this->input('description'),
            'size' => $this->input('size'),
            'capacity' => $this->filled('capacity') ? $this->integer('capacity') : null,
            'bars' => $this->integer('bars'),
            'stage' => $this->input('stage'),
            'extra' => $this->input('extra'),
        ];
    }
}
