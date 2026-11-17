<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ReceiptPdfRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.config('matrix.uploads.max_kilobytes')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => 'Das PDF fehlt.',
            'pdf.mimetypes' => 'Das hochgeladene PDF ist ungültig.',
            'pdf.max' => 'Das PDF ist zu groß.',
            'pdf.uploaded' => 'Das PDF wurde nicht vollständig hochgeladen.',
        ];
    }
}
