@inject('loadRooms', 'App\Actions\Location\LoadRoomsAction')
@php
    use App\Http\Requests\Admin\PageContentRequest;

    $content = $page->content;
    $value = fn (string $key): mixed => old($key, $content[$key] ?? null);
    $history = old('history', $content['history'] ?? []);
@endphp
<x-layouts.admin title="Location-Seite bearbeiten">
    <section class="panel form-section" id="raeume">
        <h2 class="form-section-title"><span>1</span> Räume</h2>
        <p class="hint media-hint">Beschreibung, Fläche, Kapazität, Theken, Bühne, Extra und Foto je Raum. Die Angaben erscheinen auf der Location-Seite und in den Raum-Kacheln auf Start- und Firmenseite.</p>
        <div class="room-admin-grid">
@foreach ($loadRooms->execute() as $room)
            <a class="room-admin-card" href="{{ route('admin.rooms.edit', $room->key->anchor()) }}">
                <x-room-image :room="$room" />
                <span class="room-admin-body">
                    <strong>{{ $room->key->value }}</strong>
                    <span class="muted">{{ $room->capacity ? 'bis '.$room->capacity.' Personen' : 'Kapazität fehlt' }}{{ $room->size ? ' · '.$room->size : '' }} · {{ $room->bars }} {{ $room->bars === 1 ? 'Theke' : 'Theken' }}</span>
                </span>
                <span class="btn btn-sm room-admin-edit"><x-icon name="edit" /> Bearbeiten</span>
            </a>
@endforeach
        </div>
    </section>

    <form class="page-form" method="post" action="{{ route('admin.pages.update', $pageKey) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="panel form-section">
            <h2 class="form-section-title"><span>2</span> Kopfbereich</h2>
            <div @class(['field', 'has-error' => $errors->has('hero_title')])>
                <label for="hero_title">Überschrift *</label>
                <input id="hero_title" name="hero_title" type="text" maxlength="120" required value="{{ $value('hero_title') }}">
                <x-admin.field-error name="hero_title" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('hero_lead')])>
                <label for="hero_lead">Einleitung</label>
                <textarea id="hero_lead" name="hero_lead" rows="3" maxlength="400">{{ $value('hero_lead') }}</textarea>
                <x-admin.field-error name="hero_lead" />
            </div>
        </section>

        <x-admin.page-hero-fields :page="$page" />

        <section class="panel form-section">
            <h2 class="form-section-title"><span>3</span> Geschichte</h2>
            <div @class(['field', 'has-error' => $errors->has('history_title')])>
                <label for="history_title">Überschrift</label>
                <input id="history_title" name="history_title" type="text" maxlength="120" value="{{ $value('history_title') }}">
                <x-admin.field-error name="history_title" />
            </div>
            <p class="hint media-hint">Bis zu {{ PageContentRequest::HISTORY_SLOTS }} Einträge, von oben nach unten angezeigt. Leere Zeilen werden nicht angezeigt.</p>
            <div class="history-rows">
@for ($i = 0; $i < PageContentRequest::HISTORY_SLOTS; $i++)
                <div @class(['history-row', 'has-error' => $errors->hasAny(["history.$i.year", "history.$i.text"])])>
                    <input name="history[{{ $i }}][year]" type="text" maxlength="20" value="{{ $history[$i]['year'] ?? '' }}" placeholder="Jahr" aria-label="Jahr {{ $i + 1 }}">
                    <input name="history[{{ $i }}][text]" type="text" maxlength="400" value="{{ $history[$i]['text'] ?? '' }}" placeholder="Was ist passiert?" aria-label="Text {{ $i + 1 }}">
                    <x-admin.field-error :name="'history.'.$i.'.year'" />
                    <x-admin.field-error :name="'history.'.$i.'.text'" />
                </div>
@endfor
            </div>
        </section>

        <section class="panel form-section">
            <h2 class="form-section-title"><span>4</span> Kasten „Location mieten“</h2>
            <div class="grid-2">
                <div @class(['field', 'has-error' => $errors->has('cta_title')])>
                    <label for="cta_title">Überschrift</label>
                    <input id="cta_title" name="cta_title" type="text" maxlength="120" value="{{ $value('cta_title') }}">
                    <x-admin.field-error name="cta_title" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('cta_text')])>
                    <label for="cta_text">Text</label>
                    <input id="cta_text" name="cta_text" type="text" maxlength="400" value="{{ $value('cta_text') }}">
                    <x-admin.field-error name="cta_text" />
                </div>
            </div>
        </section>

        <div class="page-save">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> Seitentexte speichern</button>
            <a class="btn btn-ghost" href="{{ route('location') }}" target="_blank" rel="noopener"><x-icon name="external" /> Seite ansehen</a>
        </div>
    </form>
</x-layouts.admin>
