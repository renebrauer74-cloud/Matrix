<?php

namespace App\Actions\Pages;

use App\Enums\MediaCollectionEnum;
use App\Models\Page;
use Illuminate\Http\UploadedFile;

/**
 * Speichert Texte, Titelbild und Titelvideo einer gepflegten Seite.
 */
class SavePageAction
{
    /**
     * @param  array<string, mixed>  $content  Felder aus PageContentRequest::pageContent()
     */
    public function execute(
        Page $page,
        array $content,
        ?UploadedFile $heroImage = null,
        bool $removeHeroImage = false,
        ?UploadedFile $heroVideo = null,
        bool $removeHeroVideo = false,
    ): Page {
        $page->content = array_replace($page->content ?? [], $content);
        $page->save();

        $this->replaceSingleFile($page, MediaCollectionEnum::PageHero, $heroImage, $removeHeroImage);
        $this->replaceSingleFile($page, MediaCollectionEnum::PageHeroVideo, $heroVideo, $removeHeroVideo);

        return $page;
    }

    private function replaceSingleFile(Page $page, MediaCollectionEnum $collection, ?UploadedFile $file, bool $remove): void
    {
        if ($file !== null) {
            $page->addMedia($file)->toMediaCollection($collection->value);
        } elseif ($remove) {
            $page->clearMediaCollection($collection->value);
        }
    }
}
