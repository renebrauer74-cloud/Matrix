<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pages\LoadPageAction;
use App\Actions\Pages\SavePageAction;
use App\Enums\Pages\PageEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageContentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function edit(PageEnum $page, LoadPageAction $loadPage): View
    {
        return view('admin.pages.'.$page->value, [
            'pageKey' => $page,
            'page' => $loadPage->execute($page),
        ]);
    }

    public function update(PageContentRequest $request, PageEnum $page, LoadPageAction $loadPage, SavePageAction $savePage): RedirectResponse
    {
        $savePage->execute(
            $loadPage->execute($page),
            $request->pageContent(),
            $request->file('hero_image'),
            $request->boolean('remove_hero_image'),
            $request->file('hero_video'),
            $request->boolean('remove_hero_video'),
        );

        return to_route('admin.pages.edit', $page)->with('success', $page->label().' gespeichert.');
    }
}
