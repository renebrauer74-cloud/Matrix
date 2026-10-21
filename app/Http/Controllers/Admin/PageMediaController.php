<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pages\AddPageMediaAction;
use App\Actions\Pages\LoadPageAction;
use App\Actions\Pages\MovePageMediaAction;
use App\Enums\Pages\PageEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageMediaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Galerie-Bilder und Videos einer gepflegten Seite: hochladen, sortieren, löschen.
 */
class PageMediaController extends Controller
{
    public function store(PageMediaRequest $request, PageEnum $page, LoadPageAction $loadPage, AddPageMediaAction $addMedia): RedirectResponse
    {
        $collection = $request->collection();
        $count = $addMedia->execute($loadPage->execute($page), $collection, $request->file('files'));
        $noun = $collection->holdsVideos()
            ? ($count === 1 ? 'Video' : 'Videos')
            : ($count === 1 ? 'Bild' : 'Bilder');

        return to_route('admin.pages.edit', $page)->withFragment($collection->value)
            ->with('success', $count.' '.$noun.' hochgeladen.');
    }

    public function move(Request $request, PageEnum $page, int $media, LoadPageAction $loadPage, MovePageMediaAction $moveMedia): RedirectResponse
    {
        $model = $loadPage->execute($page);
        $item = $model->media()->findOrFail($media);

        $moveMedia->execute($model, $item, $request->input('richtung') === 'vor');

        return to_route('admin.pages.edit', $page)->withFragment($item->collection_name);
    }

    public function destroy(PageEnum $page, int $media, LoadPageAction $loadPage): RedirectResponse
    {
        $item = $loadPage->execute($page)->media()->findOrFail($media);
        $item->delete();

        return to_route('admin.pages.edit', $page)->withFragment($item->collection_name)
            ->with('success', '„'.$item->file_name.'“ wurde gelöscht.');
    }
}
