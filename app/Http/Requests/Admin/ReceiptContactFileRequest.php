<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ReceiptContactFileRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:'.config('matrix.uploads.max_kilobytes')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Bitte eine Datei auswählen.',
            'file.mimes' => 'Bitte ein PDF oder ein Foto (JPG, PNG, WebP, HEIC) hochladen.',
            'file.max' => 'Die Datei ist zu groß (max. '.(config('matrix.uploads.max_kilobytes') / 1024).' MB).',
            'file.uploaded' => 'Die Datei ist zu groß oder wurde nicht vollständig hochgeladen.',
        ];
    }
}
