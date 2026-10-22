<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\DeleteOrganizerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizerRequest;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function index(): View
    {
        return view('admin.organizers.index', [
            'organizers' => Organizer::query()->withCount('events')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.organizers.form', [
            'organizer' => null,
            'events' => collect(),
        ]);
    }

    public function store(OrganizerRequest $request): RedirectResponse
    {
        $organizer = Organizer::query()->create($request->organizerAttributes());

        return to_route('admin.organizers.index')->with('success', '„'.$organizer->name.'“ wurde angelegt.');
    }

    public function edit(Organizer $organizer): View
    {
        return view('admin.organizers.form', [
            'organizer' => $organizer,
            'events' => $organizer->events()->latest('starts_at')->get(),
        ]);
    }

    public function update(OrganizerRequest $request, Organizer $organizer): RedirectResponse
    {
        $organizer->update($request->organizerAttributes());

        return to_route('admin.organizers.index')->with('success', 'Änderungen an „'.$organizer->name.'“ gespeichert.');
    }

    public function destroy(Organizer $organizer, DeleteOrganizerAction $deleteOrganizer): RedirectResponse
    {
        $detached = $deleteOrganizer->execute($organizer);

        return to_route('admin.organizers.index')->with('success', '„'.$organizer->name.'“ wurde gelöscht.'
            .($detached > 0 ? ' '.$detached.' '.($detached === 1 ? 'Veranstaltung läuft' : 'Veranstaltungen laufen').' jetzt als Matrix-Veranstaltung.' : ''));
    }
}
