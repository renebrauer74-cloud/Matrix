@php
    $isEdit = $organizer !== null;
@endphp
<x-layouts.admin :title="$isEdit ? 'Veranstalter: '.$organizer->name : 'Neuer Veranstalter'">
    <a class="back-link" href="{{ route('admin.organizers.index') }}"><x-icon name="arrow-left" /> Alle Veranstalter</a>

    <form class="panel narrow-panel" method="post" action="{{ $isEdit ? route('admin.organizers.update', $organizer) : route('admin.organizers.store') }}">
        @csrf
@if ($isEdit)
        @method('PUT')
@endif
        <x-admin.organizer-fields :organizer="$organizer" />
        <div class="form-buttons">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> {{ $isEdit ? 'Änderungen speichern' : 'Veranstalter anlegen' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.organizers.index') }}">Abbrechen</a>
        </div>
    </form>

@if ($isEdit)
    <section class="panel narrow-panel organizer-events">
        <h2 class="panel-title">Veranstaltungen ({{ $events->count() }})</h2>
@forelse ($events as $event)
        <a class="organizer-event" href="{{ route('admin.events.edit', $event) }}">
            <span>{{ $event->title }}</span>
            <span class="muted">{{ $event->starts_at->format('d.m.Y') }}@if ($event->isPast()) · vorbei @endif</span>
        </a>
@empty
        <p class="muted">Noch keine Veranstaltungen von diesem Veranstalter.</p>
@endforelse
    </section>

    <form class="danger-zone danger-zone-narrow" method="post" action="{{ route('admin.organizers.destroy', $organizer) }}"
          data-confirm="„{{ $organizer->name }}“ wirklich löschen?{{ $events->isNotEmpty() ? ' '.$events->count().' '.($events->count() === 1 ? 'Veranstaltung läuft' : 'Veranstaltungen laufen').' danach als Matrix-Veranstaltung.' : '' }}">
        @csrf
        @method('DELETE')
        <div>
            <strong>Veranstalter löschen</strong>
            <p class="muted">Die Veranstaltungen bleiben erhalten und laufen danach als Matrix-Veranstaltung.</p>
        </div>
        <button class="btn btn-danger" type="submit"><x-icon name="trash" /> Löschen</button>
    </form>
@endif
</x-layouts.admin>
