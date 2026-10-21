<?php

namespace App\Actions\Pages;

use App\Models\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Tauscht ein Bild oder Video mit seinem Vorgänger bzw. Nachfolger in der Collection.
 */
class MovePageMediaAction
{
    public function execute(Page $page, Media $media, bool $towardsStart): void
    {
        $ids = $page->getMedia($media->collection_name)->pluck('id')->values()->all();
        $index = array_search($media->getKey(), $ids, true);
        $target = $towardsStart ? $index - 1 : $index + 1;

        if ($index === false || ! array_key_exists($target, $ids)) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        Media::setNewOrder($ids);
    }
}
