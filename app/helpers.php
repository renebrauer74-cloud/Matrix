<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** HTML-Escaping */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Basis-Pfad der Anwendung (für Installationen in Unterordnern). */
function base_path_url(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path_url() . '/' . ltrim($path, '/');
}

/** Asset-URL mit Cache-Busting über Änderungsdatum. */
function asset(string $path): string
{
    $file = PUBLIC_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . ltrim($path, '/')) . ($v ? '?v=' . $v : '');
}

function upload_url(string $file): string
{
    return url('uploads/events/' . $file);
}

/** Aktueller Pfad ohne Basis und Query, z. B. "/programm/konzerte". */
function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path_url();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim(rawurldecode($path), '/');
}

function is_active(string $prefix, bool $exact = false): bool
{
    $p = request_path();
    $prefix = '/' . trim($prefix, '/');
    return $exact ? $p === $prefix : ($p === $prefix || str_starts_with($p, rtrim($prefix, '/') . '/'));
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target, true, 302);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref, true, 302);
        exit;
    }
    redirect($fallback);
}

function view(string $template, array $data = [], ?string $layout = 'layouts/site'): void
{
    echo View::render($template, $data, $layout);
}

function partial(string $template, array $data = []): string
{
    return View::partial($template, $data);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function flash(string $type, string $message): void
{
    $all = Session::get('_flash_next', [])['_messages'] ?? [];
    $all[] = ['type' => $type, 'message' => $message];
    Session::flash('_messages', $all);
}

function flash_messages(): array
{
    return Session::flashed('_messages', []);
}

/** Alte Formulareingabe nach Validierungsfehler. */
function old(string $key, mixed $default = ''): mixed
{
    $old = Session::flashed('_old');
    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function errors(): array
{
    return Session::flashed('_errors', []);
}

function error_for(string $field): ?string
{
    return errors()[$field] ?? null;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function slugify(string $text): string
{
    $text = strtr(mb_strtolower($text), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', '&' => ' und ']);
    if (function_exists('transliterator_transliterate')) {
        $text = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'event';
}

// --- Deutsche Datumsformate -------------------------------------------------

const DE_WEEKDAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const DE_MONTHS = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

function de_weekday(DateTimeInterface $d, bool $short = false): string
{
    $w = DE_WEEKDAYS[(int) $d->format('w')];
    return $short ? mb_substr($w, 0, 2) : $w;
}

function de_month(DateTimeInterface $d, bool $short = false): string
{
    $m = DE_MONTHS[(int) $d->format('n')];
    return $short ? mb_substr($m, 0, 3) : $m;
}

/** "Freitag, 24. Oktober 2026" */
function de_date_long(DateTimeInterface $d): string
{
    return de_weekday($d) . ', ' . $d->format('j') . '. ' . de_month($d) . ' ' . $d->format('Y');
}

/** "Fr, 24.10.2026" */
function de_date_short(DateTimeInterface $d): string
{
    return de_weekday($d, true) . ', ' . $d->format('d.m.Y');
}

/** Beschreibungstext: escapen, Links klickbar machen, Absätze erzeugen. */
function format_text(?string $text): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $html = e($text);
    $html = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)\]])~i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $html) ?? $html;
    $html = preg_replace('~\*\*(.+?)\*\*~s', '<strong>$1</strong>', $html) ?? $html;
    $paragraphs = preg_split("/\R{2,}/", $html) ?: [];
    return implode('', array_map(static fn ($p) => '<p>' . nl2br(trim($p)) . '</p>', $paragraphs));
}

function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true"><use href="' . e(asset('img/icons.svg')) . '#' . e($name) . '"></use></svg>';
}
