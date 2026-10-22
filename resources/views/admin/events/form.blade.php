@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\Events\EventOrganizerEnum;
    use App\Enums\Events\EventPublicationEnum;
    use App\Enums\Events\ImageFocusEnum;
    use App\Enums\Events\TicketStatusEnum;
    use App\Enums\Location\RoomEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $isEdit = $event !== null;
    $start = $event?->starts_at;

    $category = old('category', $presetCategory->value);
    $roomNames = array_map(fn (RoomEnum $room): string => $room->value, RoomEnum::cases());
    $storedRooms = $event?->rooms ?? [];
    $selectedRooms = (array) old('rooms', array_intersect($storedRooms, $roomNames));
    $roomCustom = old('room_custom', implode(', ', array_diff($storedRooms, $roomNames)));
    $organizer = $event?->organizer;
    $organizerMode = old('organizer', $organizer ? EventOrganizerEnum::External->value : EventOrganizerEnum::Matrix->value);
    $organizerId = (string) old('organizer_id', $organizer?->getKey());
    $imageUrl = $event?->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value) ?: null;
    $status = old('status', match (true) {
        $event?->isDraft() === true => EventPublicationEnum::Draft->value,
        $event?->isScheduled() === true => EventPublicationEnum::Scheduled->value,
        default => EventPublicationEnum::Published->value,
    });
    $scheduledAt = $event?->isScheduled() ? $event->publish_at : null;
    $publishDate = old('publish_date', $scheduledAt?->format('Y-m-d'));
    $publishTime = old('publish_time', $scheduledAt?->format('H:i'));
    $featured = (bool) old('is_featured', $event?->is_featured ?? false);
    $focus = old('image_focus', $event?->image_focus->value ?? ImageFocusEnum::Center->value);
    $ticketStatus = old('ticket_status', $event?->ticket_status->value ?? TicketStatusEnum::Available->value);
    $hasError = fn (string ...$fields): string => $errors->hasAny($fields) ? ' has-error' : '';
@endphp
<x-layouts.admin :title="$isEdit ? 'Bearbeiten: '.$event->title : 'Neue Veranstaltung'">
    <form class="event-form" method="post" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}" data-event-form novalidate>
        @csrf
@if ($isEdit)
        @method('PUT')
@endif

        <div class="form-main">
            {{-- Art --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>1</span> Art der Veranstaltung</h2>
                <div class="cat-picker{{ $hasError('category') }}" role="radiogroup" aria-label="Art der Veranstaltung">
@foreach (EventCategoryEnum::cases() as $option)
                    <label class="cat-option cat-{{ $option->value }}">
                        <input type="radio" name="category" value="{{ $option->value }}" @checked($category === $option->value) data-cat-input>
                        <span class="cat-option-box">
                            <x-icon :name="$option->icon()" class="icon cat-option-icon" />
                            <strong>{{ $option->label() }}</strong>
                            <small>{{ $option->hint() }}</small>
                        </span>
                    </label>
@endforeach
                </div>
                <x-admin.field-error name="category" />

                <div class="field{{ $hasError('category_label') }}" data-label-field>
                    <label for="category_label" data-label-text
                           data-text-sonstiges="Was für eine Veranstaltung? *"
                           data-text-default="Genre / Motto (optional)">Genre / Motto (optional)</label>
                    <input id="category_label" name="category_label" type="text" maxlength="80"
                           value="{{ old('category_label', $event?->category_label) }}"
                           list="label-suggestions"
                           data-ph-sonstiges="z. B. Lesung, Comedy, Flohmarkt, Vortrag"
                           data-ph-konzert="z. B. Metal, Punk, Tribute"
                           data-ph-party="z. B. 90er, Techno, Gothic">
                    <datalist id="label-suggestions">
                        <option value="Lesung"><option value="Comedy"><option value="Vortrag"><option value="Flohmarkt">
                        <option value="Messe"><option value="Theater"><option value="Workshop"><option value="Private Feier">
                    </datalist>
                    <x-admin.field-error name="category_label" />
                </div>
            </section>

            {{-- Basis --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>2</span> Titel &amp; Beschreibung</h2>
                <div class="field{{ $hasError('title') }}">
                    <label for="title">Titel *</label>
                    <input id="title" name="title" type="text" maxlength="200" required value="{{ old('title', $event?->title) }}" placeholder="z. B. Dirty Honey – Live in Bochum" data-preview="title">
                    <x-admin.field-error name="title" />
                </div>
                <div class="field{{ $hasError('subtitle') }}">
                    <label for="subtitle">Untertitel / Support</label>
                    <input id="subtitle" name="subtitle" type="text" maxlength="255" value="{{ old('subtitle', $event?->subtitle) }}" placeholder="z. B. Support: The Band" data-preview="subtitle">
                    <x-admin.field-error name="subtitle" />
                </div>
                <div class="field{{ $hasError('description') }}">
                    <label for="description">Beschreibung</label>
                    <textarea id="description" name="description" rows="7" placeholder="Infos zur Veranstaltung, Line-up, Hinweise …">{{ old('description', $event?->description) }}</textarea>
                    <x-admin.field-error name="description" />
                    <p class="hint">Leerzeile = neuer Absatz · <code>**fett**</code> · Links werden automatisch klickbar.</p>
                </div>
            </section>

            {{-- Termin & Ort --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>3</span> Termin &amp; Ort</h2>
                <div class="grid-4">
                    <div class="field{{ $hasError('date', 'time') }}">
                        <label for="date">Datum *</label>
                        <input id="date" name="date" type="date" required value="{{ old('date', $start?->format('Y-m-d')) }}" data-preview="date">
                    </div>
                    <div class="field{{ $hasError('date', 'time') }}">
                        <label for="time">Beginn *</label>
                        <input id="time" name="time" type="time" required value="{{ old('time', $start?->format('H:i') ?? '20:00') }}" data-preview="time">
                    </div>
                    <div class="field{{ $hasError('doors') }}">
                        <label for="doors">Einlass</label>
                        <input id="doors" name="doors" type="time" value="{{ old('doors', $event?->doors_at ? substr($event->doors_at, 0, 5) : null) }}">
                    </div>
                    <div class="field{{ $hasError('end_time') }}">
                        <label for="end_time">Ende</label>
                        <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $event?->ends_at?->format('H:i')) }}">
                    </div>
                </div>
@if ($errors->hasAny(['date', 'time']))
                <p class="field-error">{{ $errors->first('date') ?: $errors->first('time') }}</p>
@endif
                <x-admin.field-error name="doors" />
                <x-admin.field-error name="end_time" />
                <p class="hint">Endet die Veranstaltung nach Mitternacht, einfach die Uhrzeit eintragen – der Folgetag wird automatisch erkannt.</p>

                <fieldset class="field{{ $hasError('rooms', 'rooms.*') }}">
                    <legend class="label">Räume <small class="muted">– einen oder mehrere anklicken</small></legend>
                    <div class="room-picker">
@foreach ($roomNames as $roomName)
                        <label class="room-option">
                            <input type="checkbox" name="rooms[]" value="{{ $roomName }}" @checked(in_array($roomName, $selectedRooms, true)) data-room-input>
                            <span><x-icon name="check" /> {{ $roomName }}</span>
                        </label>
@endforeach
                    </div>
                    <x-admin.field-error name="rooms" />
                    <x-admin.field-error name="rooms.*" />
                </fieldset>
                <div class="field{{ $hasError('room_custom') }}">
                    <label for="room_custom">Anderer Ort (optional)</label>
                    <input id="room_custom" name="room_custom" type="text" maxlength="80" value="{{ $roomCustom }}" placeholder="z. B. Biergarten" data-room-input>
                    <x-admin.field-error name="room_custom" />
                </div>
            </section>

            {{-- Veranstalter --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>4</span> Veranstalter</h2>
                <div class="segmented segmented-full organizer-toggle" role="radiogroup" aria-label="Wer veranstaltet?">
@foreach (EventOrganizerEnum::cases() as $option)
                    <label><input type="radio" name="organizer" value="{{ $option->value }}" @checked($organizerMode === $option->value)><span><x-icon :name="$option === EventOrganizerEnum::Matrix ? 'star' : 'users'" /> {{ $option->label() }}</span></label>
@endforeach
                </div>
                <x-admin.field-error name="organizer" />

                <div class="organizer-fields" data-organizer-fields @if ($organizerMode !== EventOrganizerEnum::External->value) hidden @endif>
                    <div class="field{{ $hasError('organizer_id') }}">
                        <label for="organizer_id">Angelegten Veranstalter auswählen</label>
@if ($organizers->isNotEmpty())
                        <select id="organizer_id" name="organizer_id" data-organizer-select>
                            <option value="">– keiner, neuen Veranstalter unten eintragen –</option>
@foreach ($organizers as $option)
                            <option value="{{ $option->getKey() }}" data-organizer="{{ json_encode($option->only($option->getFillable())) }}" @selected($organizerId === (string) $option->getKey())>{{ $option->name }}{{ $option->city ? ' · '.$option->city : '' }}</option>
@endforeach
                        </select>
                        <x-admin.field-error name="organizer_id" />
                        <p class="hint">Aus dem Menü <a class="link" href="{{ route('admin.organizers.index') }}" target="_blank" rel="noopener">Veranstalter</a>. Änderungen unten gelten für alle Veranstaltungen dieses Veranstalters.</p>
@else
                        <p class="hint">Noch keine Veranstalter angelegt – einfach unten eintragen, er wird dann für weitere Veranstaltungen gespeichert.</p>
@endif
                    </div>
                    <h3 class="organizer-subtitle" data-organizer-subtitle data-text-new="Neuen Veranstalter eintragen" data-text-existing="Stammdaten des gewählten Veranstalters">{{ $organizerId !== '' && $organizers->contains('id', (int) $organizerId) ? 'Stammdaten des gewählten Veranstalters' : 'Neuen Veranstalter eintragen' }}</h3>
                    <x-admin.organizer-fields :organizer="$organizer" prefix="organizer_" />
                </div>
            </section>

            {{-- Bild --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>5</span> Bild / Flyer</h2>
                <div class="dropzone{{ $imageUrl ? ' has-image' : '' }}{{ $hasError('image') }}" data-dropzone>
                    <img class="dropzone-preview" src="{{ $imageUrl }}" alt="" data-dropzone-img @unless ($imageUrl) hidden @endunless>
                    <div class="dropzone-empty">
                        <x-icon name="upload" class="icon icon-xl" />
                        <strong>Bild hierher ziehen oder <u>auswählen</u></strong>
                        <span>JPG, PNG, WebP · max. {{ config('matrix.uploads.max_kilobytes') / 1024 }} MB · wird automatisch optimiert</span>
                    </div>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" data-dropzone-input aria-label="Bild auswählen">
                </div>
                <x-admin.field-error name="image" />
                <div class="image-options" data-image-options @unless ($imageUrl) hidden @endunless>
                    <div class="field field-inline">
                        <span class="label">Bildausschnitt in Kacheln</span>
                        <div class="segmented">
@foreach (ImageFocusEnum::cases() as $option)
                            <label><input type="radio" name="image_focus" value="{{ $option->value }}" @checked($focus === $option->value) data-preview="focus"><span>{{ $option->label() }}</span></label>
@endforeach
                        </div>
                    </div>
@if ($imageUrl)
                    <label class="check danger-check"><input type="checkbox" name="remove_image" value="1" data-remove-image> Bild entfernen</label>
@endif
                </div>
            </section>

            {{-- Tickets --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>6</span> Tickets &amp; Einlass</h2>
                <div class="field{{ $hasError('ticket_url') }}">
                    <label for="ticket_url">Ticket-Link</label>
                    <input id="ticket_url" name="ticket_url" type="url" inputmode="url" maxlength="500" value="{{ old('ticket_url', $event?->ticket_url) }}" placeholder="https://…">
                    <x-admin.field-error name="ticket_url" />
                    <p class="hint">
                        Schnell einfügen:
                        <button type="button" class="link-btn" data-fill="ticket_url" data-value="{{ config('matrix.venue.tickets_url') }}">ticket.io (Partys)</button> ·
                        <button type="button" class="link-btn" data-fill="ticket_url" data-value="{{ config('matrix.venue.concert_tickets_url') }}">Reservix (Konzerte)</button>
                    </p>
                </div>
                <div class="grid-3">
                    <div class="field{{ $hasError('price_presale') }}">
                        <label for="price_presale">Vorverkauf</label>
                        <input id="price_presale" name="price_presale" type="text" maxlength="60" value="{{ old('price_presale', $event?->price_presale) }}" placeholder="z. B. 24 €" data-preview="price">
                        <x-admin.field-error name="price_presale" />
                    </div>
                    <div class="field{{ $hasError('price_box') }}">
                        <label for="price_box">Abendkasse</label>
                        <input id="price_box" name="price_box" type="text" maxlength="60" value="{{ old('price_box', $event?->price_box) }}" placeholder="z. B. 29 €" data-preview="price">
                        <x-admin.field-error name="price_box" />
                    </div>
                    <div class="field{{ $hasError('age_rating') }}">
                        <label for="age_rating">Altersfreigabe</label>
                        <input id="age_rating" name="age_rating" type="text" maxlength="40" value="{{ old('age_rating', $event?->age_rating) }}" placeholder="z. B. ab 18" list="age-suggestions">
                        <datalist id="age-suggestions"><option value="ab 16"><option value="ab 18"><option value="ab 21"><option value="Ü30"></datalist>
                        <x-admin.field-error name="age_rating" />
                    </div>
                </div>
                <div class="field">
                    <label for="ticket_status">Ticket-Status</label>
                    <select id="ticket_status" name="ticket_status" data-preview="ticket_status">
@foreach (TicketStatusEnum::cases() as $option)
                        <option value="{{ $option->value }}" @selected($ticketStatus === $option->value)>{{ $option->label() }}</option>
@endforeach
                    </select>
                </div>
            </section>

            {{-- Social Media --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>7</span> Social Media</h2>
                <p class="hint social-intro">Links zu Künstlern, Bands oder Veranstaltern – dieselbe Plattform darf mehrfach vorkommen.</p>
                <x-admin.social-links-field :links="$event?->social_links ?? []" />
            </section>
        </div>

        <aside class="form-side">
            <div class="side-sticky">
                <section class="panel">
                    <h2 class="panel-title">Veröffentlichung</h2>
                    <div class="segmented segmented-full segmented-3" role="radiogroup" aria-label="Veröffentlichung">
@foreach (EventPublicationEnum::cases() as $option)
                        <label><input type="radio" name="status" value="{{ $option->value }}" @checked($status === $option->value) data-publication-input><span><x-icon :name="$option->icon()" /> {{ $option->label() }}</span></label>
@endforeach
                    </div>
                    <div class="publish-at{{ $hasError('publish_date', 'publish_time') }}" data-publish-at @if ($status !== EventPublicationEnum::Scheduled->value) hidden @endif>
                        <div class="grid-2">
                            <div class="field">
                                <label for="publish_date">Online ab *</label>
                                <input id="publish_date" name="publish_date" type="date" value="{{ $publishDate }}">
                            </div>
                            <div class="field">
                                <label for="publish_time">Uhrzeit *</label>
                                <input id="publish_time" name="publish_time" type="time" value="{{ $publishTime }}">
                            </div>
                        </div>
@if ($errors->hasAny(['publish_date', 'publish_time']))
                        <p class="field-error">{{ $errors->first('publish_date') ?: $errors->first('publish_time') }}</p>
@endif
                        <p class="hint">Bis dahin sehen nur eingeloggte Admins die Veranstaltung.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_featured" value="1" @checked($featured)>
                        <span class="switch-ui" aria-hidden="true"></span>
                        <span><strong>Highlight</strong><small>Groß im Slider der Startseite</small></span>
                    </label>
                    <div class="save-bar">
                        <button class="btn btn-accent btn-block" type="submit"><x-icon name="check" /> {{ $isEdit ? 'Änderungen speichern' : 'Veranstaltung speichern' }}</button>
@unless ($isEdit)
                        <button class="btn btn-ghost btn-block" type="submit" name="after" value="new">Speichern &amp; weitere anlegen</button>
@endunless
                    </div>
@if ($isEdit)
                    <div class="side-links">
                        <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener"><x-icon name="external" /> Auf der Website ansehen</a>
                    </div>
@endif
                </section>

                <section class="panel preview-panel">
                    <h2 class="panel-title">Vorschau</h2>
                    <div class="pv-card cat-{{ $category }}" data-pv>
                        <div class="pv-media">
                            <img src="{{ $imageUrl }}" alt="" data-pv-img @unless ($imageUrl) hidden @endunless style="object-position: center {{ $focus }}">
                            <div class="pv-fallback" data-pv-fallback @if ($imageUrl) hidden @endif><span data-pv-fallback-title></span></div>
                            <span class="pv-date"><b data-pv-wd>–</b><strong data-pv-day>–</strong><b data-pv-mon>–</b></span>
                            <span class="pv-ribbon" data-pv-ribbon hidden></span>
                        </div>
                        <div class="pv-body">
                            <span class="pill pill-cat"><span class="dot"></span><span data-pv-badge></span></span>
                            <strong class="pv-title" data-pv-title>Titel der Veranstaltung</strong>
                            <span class="pv-sub" data-pv-sub></span>
                            <span class="pv-meta" data-pv-meta></span>
                        </div>
                    </div>
                </section>

@if ($isEdit)
                <p class="meta-note">Erstellt {{ $event->created_at->format('d.m.Y H:i') }} · Geändert {{ $event->updated_at->format('d.m.Y H:i') }}</p>
@endif
            </div>
        </aside>
    </form>

@if ($isEdit)
    <form class="danger-zone" method="post" action="{{ route('admin.events.destroy', $event) }}" data-confirm="„{{ $event->title }}“ wirklich löschen? Das kann nicht rückgängig gemacht werden.">
        @csrf
        @method('DELETE')
        <div>
            <strong>Veranstaltung löschen</strong>
            <p class="muted">Entfernt die Veranstaltung samt Bild dauerhaft.</p>
        </div>
        <button class="btn btn-danger" type="submit"><x-icon name="trash" /> Löschen</button>
    </form>
@endif
</x-layouts.admin>
