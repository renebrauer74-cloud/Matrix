<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\CountUpcomingEventsAction;
use App\Actions\Messages\CountOpenMessagesAction;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CountUpcomingEventsAction $countUpcomingEvents, CountOpenMessagesAction $countOpenMessages): View
    {
        $counts = $countUpcomingEvents->execute();

        return view('admin.dashboard', [
            'stats' => [
                'upcoming' => $counts['all'],
                'month' => Event::query()->published()->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'drafts' => Event::query()->where('status', EventStatusEnum::Draft)->count(),
                'soldout' => Event::query()->upcoming()->where('ticket_status', TicketStatusEnum::SoldOut)->count(),
            ],
            'counts' => $counts,
            'next' => Event::query()->upcoming()->with('media')->orderBy('starts_at')->limit(6)->get(),
            'messageCounts' => $countOpenMessages->execute(),
            'latestMessages' => Message::query()->open()->latest()->latest('id')->limit(5)->get(),
        ]);
    }
}
