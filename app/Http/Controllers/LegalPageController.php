<?php

namespace App\Http\Controllers;

use App\Actions\Pages\LoadPageAction;
use App\Actions\Pages\RenderLegalTextAction;
use App\Enums\Pages\PageEnum;
use Illuminate\View\View;

/**
 * Impressum und Datenschutzerklärung – die Texte werden im Admin-Bereich gepflegt.
 */
class LegalPageController extends Controller
{
    public function imprint(LoadPageAction $loadPage, RenderLegalTextAction $renderText): View
    {
        return $this->show(PageEnum::Imprint, 'Impressum', $loadPage, $renderText);
    }

    public function privacy(LoadPageAction $loadPage, RenderLegalTextAction $renderText): View
    {
        return $this->show(PageEnum::Privacy, 'Datenschutz', $loadPage, $renderText);
    }

    private function show(PageEnum $page, string $title, LoadPageAction $loadPage, RenderLegalTextAction $renderText): View
    {
        return view('pages.legal', [
            'title' => $title,
            'body' => $renderText->execute($loadPage->execute($page)->content['body'] ?? null),
        ]);
    }
}
