<?php
declare(strict_types=1);

namespace App\Core;

use GdImage;
use RuntimeException;

/**
 * Nimmt Bilder entgegen, prüft sie und speichert sie neu kodiert als WebP
 * in zwei Größen:  {name}-lg.webp (max. 1600px)  und  {name}-md.webp (max. 760px).
 * Durch das Neu-Kodieren werden Metadaten und eingeschleuster Code verworfen.
 */
final class ImageUpload
{
    public const SIZES = ['lg' => 1600, 'md' => 760];
    private const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    /** @param array $file Eintrag aus $_FILES */
    public static function fromRequest(array $file): string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Das Bild ist zu groß.',
                UPLOAD_ERR_PARTIAL => 'Das Bild wurde nur teilweise hochgeladen.',
                default => 'Bild-Upload fehlgeschlagen (Code ' . $err . ').',
            });
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Ungültiger Upload.');
        }
        $maxBytes = (int) config('uploads.max_mb', 12) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('Das Bild ist größer als ' . config('uploads.max_mb', 12) . ' MB.');
        }
        return self::fromPath($file['tmp_name']);
    }

    public static function fromPath(string $path): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, self::MIMES, true)) {
            throw new RuntimeException('Bitte ein JPG-, PNG-, WebP-, GIF- oder AVIF-Bild hochladen.');
        }
        $info = @getimagesize($path);
        if (!$info || $info[0] < 200 || $info[1] < 200) {
            throw new RuntimeException('Das Bild ist zu klein (mindestens 200 × 200 Pixel).');
        }
        if ($info[0] * $info[1] > 40_000_000) {
            throw new RuntimeException('Das Bild hat zu viele Pixel (max. 40 Megapixel).');
        }

        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (!$src instanceof GdImage) {
            throw new RuntimeException('Das Bild konnte nicht gelesen werden.');
        }
        if ($mime === 'image/jpeg') {
            $src = self::fixOrientation($src, $path);
        }

        if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0775, true) && !is_dir(UPLOAD_PATH)) {
            throw new RuntimeException('Upload-Ordner kann nicht angelegt werden.');
        }

        $name = date('Ym') . '-' . bin2hex(random_bytes(8));
        foreach (self::SIZES as $suffix => $maxWidth) {
            imagewebp(self::resize($src, $maxWidth), UPLOAD_PATH . "/{$name}-{$suffix}.webp", 82);
        }
        return $name;
    }

    public static function delete(?string $name): void
    {
        if (!$name || !preg_match('/^[a-z0-9-]+$/i', $name)) {
            return;
        }
        foreach (array_keys(self::SIZES) as $suffix) {
            $f = UPLOAD_PATH . "/{$name}-{$suffix}.webp";
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }

    /** Kopiert ein gespeichertes Bild (z. B. beim Duplizieren eines Events). */
    public static function copy(?string $name): ?string
    {
        if (!self::exists($name)) {
            return null;
        }
        $new = date('Ym') . '-' . bin2hex(random_bytes(8));
        foreach (array_keys(self::SIZES) as $suffix) {
            copy(UPLOAD_PATH . "/{$name}-{$suffix}.webp", UPLOAD_PATH . "/{$new}-{$suffix}.webp");
        }
        return $new;
    }

    public static function exists(?string $name): bool
    {
        return $name && preg_match('/^[a-z0-9-]+$/i', $name) && is_file(UPLOAD_PATH . "/{$name}-md.webp");
    }

    private static function resize(GdImage $src, int $maxWidth): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxWidth) {
            return $src;
        }
        $nw = $maxWidth;
        $nh = (int) round($h * $maxWidth / $w);
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }

    private static function fixOrientation(GdImage $img, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $rotated = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
        return $rotated instanceof GdImage ? $rotated : $img;
    }
}
