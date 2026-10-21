<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\DuplicateEventAction;
use App\Actions\Events\ListAdminEventsAction;
use App\Actions\Events\SaveEventAction;
use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventListFilterEnum;
use App\Enums\Events\EventStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, ListAdminEventsAction $listEvents): View
    {
        $filter = $request->enum('ansicht', EventListFilterEnum::class) ?? EventListFilterEnum::Upcoming;
        $category = $request->enum('kategorie', EventCategoryEnum::class);
        $search = trim((string) $request->query('q'));

        return view('admin.events.index', [
            'events' => $listEvents->execute($filter, $category, $search),
            'filter' => $filter,
            'category' => $category,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.events.form', [
            'event' => null,
            'presetCategory' => $request->enum('art', EventCategoryEnum::class) ?? EventCategoryEnum::Konzert,
        ]);
    }

    public function store(EventRequest $request, SaveEventAction $saveEvent): RedirectResponse
    {
        $event = $saveEvent->execute(new Event, $request->eventAttributes(), $request->file('image'), false, $request->user());

        return $this->afterSave($request, $event)->with('success', '„'.$event->title.'“ wurde '
            .($event->isDraft() ? 'als Entwurf gespeichert.' : 'veröffentlicht.'));
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event,
            'presetCategory' => $event->category,
        ]);
    }

    public function update(EventRequest $request, Event $event, SaveEventAction $saveEvent): RedirectResponse
    {
        $saveEvent->execute($event, $request->eventAttributes(), $request->file('image'), $request->boolean('remove_image'), $request->user());

        return $this->afterSave($request, $event)->with('success', 'Änderungen an „'.$event->title.'“ gespeichert.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return to_route('admin.events.index')->with('success', '„'.$event->title.'“ wurde gelöscht.');
    }

    public function duplicate(Request $request, Event $event, DuplicateEventAction $duplicateEvent): RedirectResponse
    {
        $copy = $duplicateEvent->execute($event, $request->user());

        return to_route('admin.events.edit', $copy)->with('success', 'Kopie als Entwurf angelegt – Datum anpassen und veröffentlichen.');
    }

    public function toggleStatus(Event $event): RedirectResponse
    {
        $event->update(['status' => $event->isDraft() ? EventStatusEnum::Published : EventStatusEnum::Draft]);

        return back(fallback: route('admin.events.index'))->with('success', '„'.$event->title.'“ ist jetzt '
            .($event->isDraft() ? 'ein Entwurf.' : 'veröffentlicht.'));
    }

    private function afterSave(EventRequest $request, Event $event): RedirectResponse
    {
        return $request->wantsAnother()
            ? to_route('admin.events.create')
            : to_route('admin.events.edit', $event);
    }
}
