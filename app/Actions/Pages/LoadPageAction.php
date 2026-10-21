<?php

namespace App\Actions\Pages;

use App\Enums\Pages\PageEnum;
use App\Models\Page;

/**
 * Lädt eine gepflegte Seite – fehlende Texte werden mit den Ausgangstexten aus PageEnum ergänzt.
 *
 * Gibt es die Seite noch nicht, kommt ein ungespeichertes Model zurück; angelegt wird sie beim ersten Speichern.
 */
class LoadPageAction
{
    public function execute(PageEnum $key): Page
    {
        $page = Page::query()->with('media')->firstOrNew(['key' => $key]);
        $page->content = array_replace($key->defaults(), $page->content ?? []);

        return $page;
    }
}
