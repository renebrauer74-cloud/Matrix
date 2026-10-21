@php
    $isEdit = $position !== null;
    $active = (bool) old('is_active', $position?->is_active ?? true);
@endphp
<x-layouts.admin :title="$isEdit ? 'Stelle: '.$position->title : 'Neue Stelle'">
    <a class="back-link" href="{{ route('admin.job-positions.index') }}"><x-icon name="arrow-left" /> Alle Stellen</a>

    <form class="panel narrow-panel" method="post" action="{{ $isEdit ? route('admin.job-positions.update', $position) : route('admin.job-positions.store') }}">
        @csrf
@if ($isEdit)
        @method('PUT')
@endif
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('title')])>
                <label for="title">Titel *</label>
                <input id="title" name="title" type="text" maxlength="120" required value="{{ old('title', $position?->title) }}" placeholder="z. B. Service an der Theke">
                <x-admin.field-error name="title" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('employment')])>
                <label for="employment">Anstellung</label>
                <input id="employment" name="employment" type="text" maxlength="80" value="{{ old('employment', $position?->employment) }}" placeholder="z. B. Minijob · Aushilfe" list="employment-suggestions">
                <datalist id="employment-suggestions"><option value="Minijob · Aushilfe"><option value="Minijob"><option value="Teilzeit"><option value="Vollzeit"><option value="Werkstudent"></datalist>
                <x-admin.field-error name="employment" />
            </div>
        </div>
        <div @class(['field', 'has-error' => $errors->has('summary')])>
            <label for="summary">Kurzbeschreibung</label>
            <input id="summary" name="summary" type="text" maxlength="255" value="{{ old('summary', $position?->summary) }}" placeholder="Ein Satz, der Lust auf den Job macht">
            <x-admin.field-error name="summary" />
        </div>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('description')])>
                <label for="description">Aufgaben</label>
                <textarea id="description" name="description" rows="6" placeholder="Eine Aufgabe pro Zeile">{{ old('description', $position?->description) }}</textarea>
                <x-admin.field-error name="description" />
                <p class="hint">Eine Aufgabe pro Zeile – wird als Liste angezeigt.</p>
            </div>
            <div @class(['field', 'has-error' => $errors->has('requirements')])>
                <label for="requirements">Das bringst du mit</label>
                <textarea id="requirements" name="requirements" rows="6" placeholder="Eine Angabe pro Zeile">{{ old('requirements', $position?->requirements) }}</textarea>
                <x-admin.field-error name="requirements" />
                <p class="hint">Eine Angabe pro Zeile.</p>
            </div>
        </div>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('sort_order')])>
                <label for="sort_order">Reihenfolge</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $nextSortOrder) }}">
                <x-admin.field-error name="sort_order" />
                <p class="hint">Kleinere Zahl = weiter vorne.</p>
            </div>
            <div class="field">
                <span class="label">Sichtbarkeit</span>
                <label class="switch switch-accent">
                    <input type="checkbox" name="is_active" value="1" @checked($active)>
                    <span class="switch-ui" aria-hidden="true"></span>
                    <span><strong>Auf der Website zeigen</strong><small>Aus = ausgeblendet, z. B. wenn die Stelle besetzt ist</small></span>
                </label>
            </div>
        </div>
        <div class="form-buttons">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> {{ $isEdit ? 'Änderungen speichern' : 'Stelle anlegen' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.job-positions.index') }}">Abbrechen</a>
        </div>
    </form>

@if ($isEdit)
    <form class="danger-zone danger-zone-narrow" method="post" action="{{ route('admin.job-positions.destroy', $position) }}" data-confirm="„{{ $position->title }}“ wirklich löschen?">
        @csrf
        @method('DELETE')
        <div>
            <strong>Stelle löschen</strong>
            <p class="muted">Bereits eingegangene Bewerbungen bleiben im Posteingang erhalten.</p>
        </div>
        <button class="btn btn-danger" type="submit"><x-icon name="trash" /> Löschen</button>
    </form>
@endif
</x-layouts.admin>
