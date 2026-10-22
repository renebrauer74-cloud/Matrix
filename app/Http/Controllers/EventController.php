<?php

namespace App\Http\Controllers;

use App\Actions\Events\BuildEventCalendarAction;
use App\Actions\Events\CountUpcomingEventsAction;
use App\Enums\Events\EventCategoryEnum;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, CountUpcomingEventsAction $countUpcomingEvents, ?string $kategorie = null): View
    {
        $category = $kategorie !== null ? EventCategoryEnum::fromSlug($kategorie) : null;

        $rooms = Event::query()
            ->published()
            ->upcoming()
            ->whereNotNull('rooms')
            ->pluck('rooms')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $room = $rooms->contains($request->query('raum')) ? $request->query('raum') : null;

        $events = Event::query()
            ->published()
            ->upcoming()
            ->with('media')
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($room, fn ($query) => $query->whereJsonContains('rooms', $room))
            ->orderBy('starts_at')
            ->get();

        return view('events.index', [
            'category' => $category,
            'months' => $events->groupBy(fn (Event $event): string => $event->starts_at->format('Y-m')),
            'total' => $events->count(),
            'counts' => $countUpcomingEvents->execute(),
            'rooms' => $rooms,
            'room' => $room,
        ]);
    }

    public function show(Event $event): View
    {
        $this->ensureVisible($event);

        return view('events.show', [
            'event' => $event,
            'related' => Event::query()
                ->published()
                ->upcoming()
                ->with('media')
                ->whereKeyNot($event->getKey())
                ->orderByRaw('(category = ?) desc', [$event->category->value])
                ->orderBy('starts_at')
                ->limit(3)
                ->get(),
        ]);
    }

    public function calendar(Event $event, BuildEventCalendarAction $buildCalendar): Response
    {
        $this->ensureVisible($event);

        return response($buildCalendar->execute($event), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    /**
     * Entwürfe und noch nicht freigeschaltete geplante Veranstaltungen sehen nur eingeloggte Admins.
     */
    private function ensureVisible(Event $event): void
    {
        abort_if(! $event->isOnline() && ! auth()->check(), 404);
    }
}
