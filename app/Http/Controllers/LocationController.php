<?php

namespace App\Http\Controllers;

use App\Actions\Location\LoadRoomsAction;
use App\Actions\Pages\LoadPageAction;
use App\Enums\Pages\PageEnum;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function show(LoadPageAction $loadPage, LoadRoomsAction $loadRooms): View
    {
        return view('pages.location', [
            'page' => $loadPage->execute(PageEnum::Location),
            'rooms' => $loadRooms->execute(),
        ]);
    }
}
