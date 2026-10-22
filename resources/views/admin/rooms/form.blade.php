@php
    use App\Enums\MediaCollectionEnum;

    $uploaded = $room->exists ? $room->getFirstMedia(MediaCollectionEnum::RoomImage->value) : null;
@endphp
<x-layouts.admin :title="'Raum: '.$room->key->value">
    <a class="back-link" href="{{ route('admin.pages.edit', App\Enums\Pages\PageEnum::Location) }}#raeume"><x-icon name="arrow-left" /> Location-Seite</a>

    <form class="panel narrow-panel" method="post" action="{{ route('admin.rooms.update', $room->key->anchor()) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div @class(['field', 'has-error' => $errors->has('description')])>
            <label for="description">Beschreibung</label>
            <textarea id="description" name="description" rows="5" maxlength="2000">{{ old('description', $room->description) }}</textarea>
            <x-admin.field-error name="description" />
        </div>
        <div class="grid-3">
            <div @class(['field', 'has-error' => $errors->has('size')])>
                <label for="size">Fläche</label>
                <input id="size" name="size" type="text" maxlength="40" value="{{ old('size', $room->size) }}" placeholder="z. B. 360 m²">
                <x-admin.field-error name="size" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('capacity')])>
                <label for="capacity">Kapazität (Personen)</label>
                <input id="capacity" name="capacity" type="number" min="1" max="100000" inputmode="numeric" value="{{ old('capacity', $room->capacity) }}" placeholder="z. B. 700">
                <x-admin.field-error name="capacity" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('bars')])>
                <label for="bars">Theken *</label>
                <input id="bars" name="bars" type="number" min="0" max="50" inputmode="numeric" required value="{{ old('bars', $room->bars) }}">
                <x-admin.field-error name="bars" />
            </div>
        </div>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('stage')])>
                <label for="stage">Bühne</label>
                <input id="stage" name="stage" type="text" maxlength="80" value="{{ old('stage', $room->stage) }}" placeholder="z. B. 8,5 × 7,5 × 1 m">
                <x-admin.field-error name="stage" />
                <p class="hint">Leer lassen, wenn es keine Bühne gibt.</p>
            </div>
            <div @class(['field', 'has-error' => $errors->has('extra')])>
                <label for="extra">Extra</label>
                <input id="extra" name="extra" type="text" maxlength="80" value="{{ old('extra', $room->extra) }}" placeholder="z. B. Backstage 170 m²">
                <x-admin.field-error name="extra" />
                <p class="hint">Leer lassen, wenn es nichts Besonderes gibt.</p>
            </div>
        </div>

        <div @class(['field', 'has-error' => $errors->has('image')])>
            <span class="label">Foto</span>
            <x-room-image :room="$room" class="media-current" loading="eager" />
@if ($uploaded)
            <label class="check danger-check"><input type="checkbox" name="remove_image" value="1"> Eigenes Foto entfernen (dann wird wieder das Standardfoto gezeigt)</label>
@else
            <p class="hint">Zurzeit das mitgelieferte Standardfoto.</p>
@endif
            <label class="dropzone dropzone-sm" data-upload>
                <span class="dropzone-empty">
                    <x-icon name="upload" class="icon icon-xl" />
                    <strong>{{ $uploaded ? 'Neues Foto wählen' : 'Eigenes Foto hochladen' }}</strong>
                    <span>Querformat, am besten 1600 px breit · max. {{ config('matrix.uploads.max_kilobytes') / 1024 }} MB</span>
                    <span class="dropzone-names" data-upload-names></span>
                </span>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" aria-label="Foto auswählen">
            </label>
            <x-admin.field-error name="image" />
        </div>

        <div class="form-buttons">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> Raum speichern</button>
            <a class="btn btn-ghost" href="{{ route('location') }}#{{ $room->key->anchor() }}" target="_blank" rel="noopener"><x-icon name="external" /> Auf der Website ansehen</a>
        </div>
    </form>
</x-layouts.admin>
