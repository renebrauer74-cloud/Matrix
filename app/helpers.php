<?php

use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * View-Helfer für Datumsangaben und Beschreibungstexte.
 */
if (! function_exists('de_date_long')) {
    /**
     * „Freitag, 24. Oktober 2026“
     */
    function de_date_long(CarbonInterface $date): string
    {
        return $date->translatedFormat('l, j. F Y');
    }
}

if (! function_exists('de_date_short')) {
    /**
     * „Fr, 24.10.2026“
     */
    function de_date_short(CarbonInterface $date): string
    {
        return $date->minDayName.', '.$date->format('d.m.Y');
    }
}

if (! function_exists('de_month_short')) {
    /**
     * „Okt“, „Mär“ – drei Buchstaben ohne Punkt, wie im Datums-Badge der Kacheln.
     */
    function de_month_short(CarbonInterface $date): string
    {
        return Str::substr($date->monthName, 0, 3);
    }
}

if (! function_exists('format_text')) {
    /**
     * Beschreibungstext aus dem Admin: escapen, Links klickbar machen, **fett** und Absätze erzeugen.
     */
    function format_text(?string $text): HtmlString
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new HtmlString('');
        }

        $html = e($text);
        $html = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)\]])~i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $html) ?? $html;
        $html = preg_replace('~\*\*(.+?)\*\*~s', '<strong>$1</strong>', $html) ?? $html;
        $paragraphs = preg_split("/\R{2,}/", $html) ?: [];

        return new HtmlString(implode('', array_map(
            fn (string $paragraph): string => '<p>'.nl2br(trim($paragraph)).'</p>',
            $paragraphs,
        )));
    }
}

if (! function_exists('phone_link')) {
    /**
     * Telefonnummer für tel:-Links, z. B. „0234292102“.
     */
    function phone_link(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', $phone) ?? '';
    }
}
