<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis für die Admin-Formulare. Der Zugriff ist bereits per auth-Middleware geschützt.
 */
abstract class AdminFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regeln für das Bild aus der Drag-&-Drop-Zone.
     *
     * @return list<string>
     */
    protected function imageRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,gif,webp,avif',
            'max:'.config('matrix.uploads.max_kilobytes'),
            'dimensions:min_width=200,min_height=200',
        ];
    }

    /**
     * Regeln für ein Video (MP4, WebM oder MOV).
     *
     * @return list<string>
     */
    protected function videoRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimetypes:video/mp4,application/mp4,video/webm,video/quicktime',
            'max:'.config('matrix.uploads.video_max_kilobytes'),
        ];
    }

    /**
     * Fehlermeldungen für Bild- und Video-Uploads der genannten Felder.
     *
     * @return array<string, string>
     */
    protected function uploadMessages(string ...$fields): array
    {
        $messages = [];

        foreach ($fields as $field) {
            $messages += [
                $field.'.image' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
                $field.'.mimes' => 'Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.',
                $field.'.mimetypes' => 'Bitte ein Video als MP4, WebM oder MOV hochladen.',
                $field.'.dimensions' => 'Das Bild ist zu klein (mindestens 200 × 200 Pixel).',
                $field.'.uploaded' => 'Die Datei ist zu groß oder wurde nicht vollständig hochgeladen.',
                $field.'.max' => 'Die Datei ist zu groß (Bilder max. '.(config('matrix.uploads.max_kilobytes') / 1024)
                    .' MB, Videos max. '.(config('matrix.uploads.video_max_kilobytes') / 1024).' MB).',
            ];
        }

        return $messages;
    }
}
