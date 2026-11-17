<?php

namespace App\Actions\Receipts;

/**
 * Liefert eine Datei der Quittungs-App (resources/quittung), passend zur Adresse im Admin-Bereich.
 *
 * Die App baut die PDFs im Browser; Firmen, Kontakte, Mitarbeiter und Quittungen speichert sie über die
 * Schnittstelle unter /admin/quittung/api im gemeinsamen Konto.
 */
class LoadReceiptAppFileAction
{
    /**
     * Erlaubte Dateien mit ihrem Content-Type – alles andere gibt es nicht.
     *
     * @var array<string, string>
     */
    private const FILES = [
        'index.html' => 'text/html; charset=utf-8',
        'app.js' => 'text/javascript; charset=utf-8',
        'style.css' => 'text/css; charset=utf-8',
        'sw.js' => 'text/javascript; charset=utf-8',
        'manifest.webmanifest' => 'application/manifest+json; charset=utf-8',
        'icons/icon-192.png' => 'image/png',
        'icons/icon-512.png' => 'image/png',
        'icons/apple-touch-icon.png' => 'image/png',
        'vendor/jspdf.umd.min.js' => 'text/javascript; charset=utf-8',
        'vendor/jspdf-LICENSE.txt' => 'text/plain; charset=utf-8',
    ];

    /**
     * @param  string  $appUrl  Adresse der App ohne abschließenden Schrägstrich, z. B. https://matrix.de/admin/quittung
     * @return array{content: string, headers: array<string, string>}|null
     */
    public function execute(string $path, string $appUrl): ?array
    {
        if (! array_key_exists($path, self::FILES)) {
            return null;
        }

        $appPath = parse_url($appUrl, PHP_URL_PATH) ?: '/';
        $content = (string) file_get_contents(resource_path('quittung/'.$path));
        $headers = [
            'Content-Type' => self::FILES[$path],
            'Cache-Control' => 'no-cache, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];

        if ($path === 'index.html') {
            $content = str_replace(
                '<meta charset="utf-8">',
                '<meta charset="utf-8">'."\n".'<base href="'.e($appUrl).'/">'."\n".'<meta name="sw-scope" content="'.e($appPath).'">',
                $content,
            );
        }

        if ($path === 'manifest.webmanifest') {
            $manifest = array_merge(json_decode($content, true), ['start_url' => $appPath, 'scope' => $appPath]);
            $content = (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($path === 'sw.js') {
            $headers['Service-Worker-Allowed'] = $appPath;
        }

        return ['content' => $content, 'headers' => $headers];
    }
}
