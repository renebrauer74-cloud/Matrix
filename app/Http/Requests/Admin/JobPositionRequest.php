<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class JobPositionRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'employment' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Bitte einen Titel eingeben, z. B. „Service an der Theke“.',
            'sort_order.*' => 'Bitte eine Zahl zwischen 0 und 9999 angeben.',
        ];
    }

    /**
     * Spalten der Tabelle job_positions aus den Formularfeldern.
     *
     * @return array<string, mixed>
     */
    public function positionAttributes(): array
    {
        return [
            'title' => $this->input('title'),
            'summary' => $this->input('summary'),
            'description' => $this->input('description'),
            'requirements' => $this->input('requirements'),
            'employment' => $this->input('employment'),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
