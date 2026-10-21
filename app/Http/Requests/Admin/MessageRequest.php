<?php

namespace App\Http\Requests\Admin;

use App\Enums\Messages\MessageStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Status und interne Notiz einer Nachricht im Posteingang.
 */
class MessageRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(MessageStatusEnum::class)],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
