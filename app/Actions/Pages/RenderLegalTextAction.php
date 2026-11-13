<?php

namespace App\Actions\Pages;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Wandelt den im Admin gepflegten Rechtstext (Impressum, Datenschutz) in HTML um.
 *
 * Einfache Formatierung: „## Überschrift“, Leerzeile = neuer Absatz, Zeilenumbruch bleibt erhalten,
 * „**fett**“, „- “ für Aufzählungen. Links und E-Mail-Adressen werden automatisch klickbar.
 * Eingefügtes HTML wird nicht ausgeführt, sondern als Text angezeigt.
 */
class RenderLegalTextAction
{
    public function execute(?string $text): HtmlString
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'renderer' => ['soft_break' => "<br>\n"],
        ]));
    }
}
