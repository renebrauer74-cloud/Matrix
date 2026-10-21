<?php

namespace App\Http\Requests\Site;

use App\Enums\Messages\ContactTopicEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ContactRequest extends SiteFormRequest
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
            'topic' => ['required', Rule::enum(ContactTopicEnum::class)],
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'topic.required' => 'Bitte wähle ein Thema aus.',
            'body.required' => 'Bitte schreib uns, worum es geht.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messageAttributes(): array
    {
        $topic = $this->enum('topic', ContactTopicEnum::class);

        return [
            ...$this->contactAttributes(),
            'subject' => $this->input('subject') ?: $topic->label(),
            'body' => $this->input('body'),
            'details' => ['topic' => $topic->value],
        ];
    }
}
