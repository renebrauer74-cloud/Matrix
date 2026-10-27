<?php

namespace App\Http\Controllers;

use App\Actions\Events\CountUpcomingEventsAction;
use App\Actions\Location\LoadRoomsAction;
use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CountUpcomingEventsAction $countUpcomingEvents, LoadRoomsAction $loadRooms): View
    {
        $upcoming = fn () => Event::query()->published()->upcoming()->with(['media', 'genres']);

        return view('home', [
            // Highlights im eingestellten Zeitraum zuerst, danach mit den nächsten Terminen aufgefüllt
            'slides' => $upcoming()
                ->highlightsFirst()
                ->orderBy('starts_at')
                ->limit(config('matrix.home.slides'))
                ->get(),
            'events' => $upcoming()
                ->orderBy('starts_at')
                ->limit(config('matrix.home.events'))
                ->get(),
            'counts' => $countUpcomingEvents->execute(),
            'rooms' => $loadRooms->execute(),
        ]);
    }
}
