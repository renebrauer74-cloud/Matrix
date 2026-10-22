<?php

namespace App\Http\Requests\Admin;

use App\Enums\Social\SocialPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Basis für die Admin-Formulare. Der Zugriff ist bereits per auth-Middleware geschützt.
 */
abstract class AdminFormRequest extends FormRequest
{
    /**
     * Höchstzahl der Social-Media-Links in einem Formular.
     */
    public const SOCIAL_LINK_SLOTS = 20;

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
     * Links ohne http(s):// bekommen https:// vorangestellt – z. B. „instagram.com/matrix“.
     */
    protected function prepareSocialLinks(): void
    {
        $links = $this->input('social_links');

        if (! is_array($links)) {
            return;
        }

        $this->merge(['social_links' => array_map(function (mixed $link): mixed {
            if (! is_array($link)) {
                return $link;
            }

            $url = trim((string) ($link['url'] ?? ''));

            return [...$link, 'url' => $url !== '' && ! preg_match('#^https?://#i', $url) ? 'https://'.$url : $url];
        }, $links)]);
    }

    /**
     * Regeln für die Social-Media-Links (Plattform + Link, beliebig oft).
     *
     * @return array<string, array<mixed>>
     */
    protected function socialLinkRules(): array
    {
        return [
            'social_links' => ['nullable', 'array', 'max:'.self::SOCIAL_LINK_SLOTS],
            'social_links.*.platform' => ['required_with:social_links.*.url', Rule::enum(SocialPlatformEnum::class)],
            'social_links.*.url' => ['nullable', 'url:http,https', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function socialLinkMessages(): array
    {
        return [
            'social_links.max' => 'Bitte höchstens '.self::SOCIAL_LINK_SLOTS.' Links angeben.',
            'social_links.*.platform' => 'Bitte Instagram, Facebook, TikTok oder YouTube auswählen.',
            'social_links.*.url' => 'Bitte einen gültigen Link angeben.',
        ];
    }

    /**
     * Ausgefüllte Social-Media-Links in Formular-Reihenfolge, leere Zeilen fallen weg.
     *
     * @return list<array{platform: string, url: string}>
     */
    public function socialLinks(): array
    {
        return collect($this->input('social_links', []))
            ->filter(fn (mixed $link): bool => is_array($link) && filled($link['url'] ?? null))
            ->map(fn (array $link): array => ['platform' => (string) $link['platform'], 'url' => (string) $link['url']])
            ->values()
            ->all();
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
