<x-layouts.admin title="Veranstalter">
    <div class="list-toolbar">
        <p class="muted toolbar-note">Externe Veranstalter mit ihren Stammdaten. Neue Veranstalter entstehen auch direkt beim Anlegen einer Veranstaltung.</p>
        <div class="toolbar-actions">
            <a class="btn btn-accent" href="{{ route('admin.organizers.create') }}"><x-icon name="plus" /> Neuer Veranstalter</a>
        </div>
    </div>

@if ($organizers->isEmpty())
    <div class="panel empty">
        <x-icon name="stage" class="icon icon-xl" />
        <p>Noch keine externen Veranstalter – bisher veranstaltet die Matrix alles selbst.</p>
        <a class="btn btn-accent" href="{{ route('admin.organizers.create') }}"><x-icon name="plus" /> Ersten Veranstalter anlegen</a>
    </div>
@else
    <div class="position-list" role="list">
@foreach ($organizers as $organizer)
@php
    $address = trim(implode(', ', array_filter([$organizer->street, trim($organizer->zip.' '.$organizer->city)])));
    $contact = implode(' · ', array_filter([$organizer->contact_person, $organizer->phone, $organizer->email]));
    $confirm = '„'.$organizer->name.'“ wirklich löschen?'
        .($organizer->events_count > 0 ? ' '.$organizer->events_count.' '.($organizer->events_count === 1 ? 'Veranstaltung läuft' : 'Veranstaltungen laufen').' danach als Matrix-Veranstaltung.' : '');
@endphp
        <article class="prow orow" role="listitem">
            <div class="prow-main">
                <a class="prow-title" href="{{ route('admin.organizers.edit', $organizer) }}">{{ $organizer->name }}</a>
                <span class="muted">{{ $address ?: 'Keine Anschrift hinterlegt' }}</span>
@if ($contact !== '')
                <span class="muted">{{ $contact }}</span>
@endif
            </div>
            <div class="prow-status">
                <span class="tag">{{ $organizer->events_count }} {{ $organizer->events_count === 1 ? 'Veranstaltung' : 'Veranstaltungen' }}</span>
            </div>
            <div class="erow-actions">
                <a class="icon-btn" href="{{ route('admin.organizers.edit', $organizer) }}" title="Bearbeiten"><x-icon name="edit" /><span class="sr-only">Bearbeiten</span></a>
                <form method="post" action="{{ route('admin.organizers.destroy', $organizer) }}" data-confirm="{{ $confirm }}">
                    @csrf
                    @method('DELETE')
                    <button class="icon-btn icon-btn-danger" type="submit" title="Löschen"><x-icon name="trash" /><span class="sr-only">Löschen</span></button>
                </form>
            </div>
        </article>
@endforeach
    </div>
@endif
</x-layouts.admin>
