<?php

namespace App\Actions\Pages;

use App\Enums\MediaCollectionEnum;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Hängt Bilder oder Videos hinten an Galerie bzw. Videoliste einer gepflegten Seite an.
 */
class AddPageMediaAction
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function execute(Page $page, MediaCollectionEnum $collection, array $files): int
    {
        return DB::transaction(function () use ($page, $collection, $files): int {
            if (! $page->exists) {
                $page->save();
            }

            foreach ($files as $file) {
                $page->addMedia($file)->toMediaCollection($collection->value);
            }

            return count($files);
        });
    }
}
