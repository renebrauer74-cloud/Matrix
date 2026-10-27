<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\ApplyEventToSeriesAction;
use App\Actions\Events\CreateEventSeriesAction;
use App\Actions\Events\DeleteEventAction;
use App\Actions\Events\DuplicateEventAction;
use App\Actions\Events\ListAdminEventsAction;
use App\Actions\Events\ResolveGenresAction;
use App\Actions\Events\SaveEventAction;
use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventListFilterEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\SeriesScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Models\Genre;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, ListAdminEventsAction $listEvents): View
    {
        $filter = $request->enum('ansicht', EventListFilterEnum::class) ?? EventListFilterEnum::Upcoming;
        $category = $request->enum('kategorie', EventCategoryEnum::class);
        $search = trim((string) $request->query('q'));
        $seriesId = Str::isUuid((string) $request->query('serie')) ? (string) $request->query('serie') : null;

        return view('admin.events.index', [
            'events' => $listEvents->execute($filter, $category, $search, $seriesId),
            'filter' => $filter,
            'category' => $category,
            'search' => $search,
            'seriesId' => $seriesId,
        ]);
    }

    /**
     * Neue Veranstaltung – mit ?vorlage={id} sind alle Angaben einer bestehenden übernommen („Erneut ansetzen“).
     */
    public function create(Request $request): View
    {
        $template = $request->filled('vorlage') ? Event::query()->with('genres')->find($request->integer('vorlage')) : null;

        return view('admin.events.form', [
            'event' => null,
            'template' => $template,
            'presetCategory' => $template?->category ?? $request->enum('art', EventCategoryEnum::class) ?? EventCategoryEnum::Konzert,
            'organizers' => Organizer::query()->orderBy('name')->get(),
            'genres' => Genre::query()->withCount('events')->orderBy('name')->get(),
            'seriesCount' => 0,
        ]);
    }

    public function store(EventRequest $request, SaveEventAction $saveEvent, ResolveGenresAction $resolveGenres, CreateEventSeriesAction $createSeries): RedirectResponse
    {
        $event = $saveEvent->execute(
            new Event,
            $request->eventAttributes(),
            $request->organizerAttributes(),
            $request->organizerId(),
            $request->file('image'),
            false,
            $request->user(),
            genreIds: $resolveGenres->execute($request->genreIds(), $request->newGenreNames()),
            video: $request->file('video'),
            template: $request->template(),
        );
        $created = $createSeries->execute($event, $request->seriesDates(), $request->user());

        $message = '„'.$event->title.'“ wurde '.match (true) {
            $event->isDraft() => 'als Entwurf gespeichert',
            $event->isScheduled() => 'gespeichert und geht am '.$event->publish_at->format('d.m.Y \u\m H:i').' Uhr online',
            default => 'veröffentlicht',
        };

        return $this->afterSave($request, $event)->with('success', $message.($created > 0 ? ' – plus '.$this->dates($created, 'weiterer Termin', 'weitere Termine').'.' : '.'));
    }

    public function edit(Event $event): View
    {
        $event->load('genres');

        return view('admin.events.form', [
            'event' => $event,
            'template' => null,
            'presetCategory' => $event->category,
            'organizers' => Organizer::query()->orderBy('name')->get(),
            'genres' => Genre::query()->withCount('events')->orderBy('name')->get(),
            'seriesCount' => $event->isSeries() ? $event->seriesEvents()->count() : 0,
        ]);
    }

    public function update(EventRequest $request, Event $event, SaveEventAction $saveEvent, ResolveGenresAction $resolveGenres, ApplyEventToSeriesAction $applyToSeries, CreateEventSeriesAction $createSeries): RedirectResponse
    {
        $saveEvent->execute(
            $event,
            $request->eventAttributes(),
            $request->organizerAttributes(),
            $request->organizerId(),
            $request->file('image'),
            $request->boolean('remove_image'),
            $request->user(),
            genreIds: $resolveGenres->execute($request->genreIds(), $request->newGenreNames()),
            video: $request->file('video'),
            removeVideo: $request->boolean('remove_video'),
        );
        $applied = $applyToSeries->execute($event, $request->seriesScope(), $request->changesMedia());
        $created = $createSeries->execute($event, $request->seriesDates(), $request->user());

        $message = 'Änderungen an „'.$event->title.'“ gespeichert'
            .($applied > 0 ? ' und auf '.$this->dates($applied, 'weiteren Termin', 'weitere Termine').' der Serie übertragen' : '')
            .($created > 0 ? ' – '.$this->dates($created, 'neuer Termin', 'neue Termine').' angelegt' : '');

        return $this->afterSave($request, $event)->with('success', $message.'.');
    }

    public function destroy(Request $request, Event $event, DeleteEventAction $deleteEvent): RedirectResponse
    {
        $deleted = $deleteEvent->execute($event, $request->enum('scope', SeriesScopeEnum::class) ?? SeriesScopeEnum::Single);

        return to_route('admin.events.index')->with('success', $deleted > 1
            ? $deleted.' Termine der Serie „'.$event->title.'“ wurden gelöscht.'
            : '„'.$event->title.'“ wurde gelöscht.');
    }

    public function duplicate(Request $request, Event $event, DuplicateEventAction $duplicateEvent): RedirectResponse
    {
        $copy = $duplicateEvent->execute($event, $request->user());

        return to_route('admin.events.edit', $copy)->with('success', 'Kopie als Entwurf angelegt – Datum anpassen und veröffentlichen.');
    }

    public function toggleStatus(Event $event): RedirectResponse
    {
        $event->update([
            'status' => $event->isOnline() ? EventStatusEnum::Draft : EventStatusEnum::Published,
            'publish_at' => null,
        ]);

        return back(fallback: route('admin.events.index'))->with('success', '„'.$event->title.'“ ist jetzt '
            .($event->isDraft() ? 'ein Entwurf.' : 'veröffentlicht.'));
    }

    /**
     * „1 weiterer Termin“ / „3 weitere Termine“.
     */
    private function dates(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }

    private function afterSave(EventRequest $request, Event $event): RedirectResponse
    {
        return $request->wantsAnother()
            ? to_route('admin.events.create')
            : to_route('admin.events.edit', $event);
    }
}
