@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\Events\EventOrganizerEnum;
    use App\Enums\Events\EventPublicationEnum;
    use App\Enums\Events\HighlightEndEnum;
    use App\Enums\Events\HighlightStartEnum;
    use App\Enums\Events\ImageFocusEnum;
    use App\Enums\Events\RecurrenceEndEnum;
    use App\Enums\Events\RecurrenceEnum;
    use App\Enums\Events\SeriesScopeEnum;
    use App\Enums\Events\TicketStatusEnum;
    use App\Enums\Location\RoomEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $isEdit = $event !== null;
    // Werte kommen aus der bearbeiteten Veranstaltung oder – bei „Erneut ansetzen“ – aus der Vorlage
    $source = $event ?? $template;
    $start = $event?->starts_at;

    $category = old('category', $presetCategory->value);
    $roomNames = array_map(fn (RoomEnum $room): string => $room->value, RoomEnum::cases());
    $storedRooms = $source?->rooms ?? [];
    $selectedRooms = (array) old('rooms', array_intersect($storedRooms, $roomNames));
    $roomCustom = old('room_custom', implode(', ', array_diff($storedRooms, $roomNames)));
    $organizer = $source?->organizer;
    $organizerMode = old('organizer', $organizer ? EventOrganizerEnum::External->value : EventOrganizerEnum::Matrix->value);
    $organizerId = (string) old('organizer_id', $organizer?->getKey());
    $imageUrl = $source?->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value) ?: null;
    $videoMedia = $source?->getFirstMedia(MediaCollectionEnum::EventVideo->value);
    $status = old('status', match (true) {
        $event?->isDraft() === true => EventPublicationEnum::Draft->value,
        $event?->isScheduled() === true => EventPublicationEnum::Scheduled->value,
        default => EventPublicationEnum::Published->value,
    });
    $scheduledAt = $event?->isScheduled() ? $event->publish_at : null;
    $publishDate = old('publish_date', $scheduledAt?->format('Y-m-d'));
    $publishTime = old('publish_time', $scheduledAt?->format('H:i'));
    $focus = old('image_focus', $source?->image_focus->value ?? ImageFocusEnum::Center->value);
    $ticketStatus = old('ticket_status', $event?->ticket_status->value ?? TicketStatusEnum::Available->value);
    $selectedGenres = array_map('intval', (array) old('genres', $source?->genres->modelKeys() ?? []));

    // Highlight-Zeitraum
    $featured = (bool) old('is_featured', $event?->is_featured ?? false);
    $highlightStart = old('featured_start', match (true) {
        $event?->featured_lead_days !== null && HighlightStartEnum::fromLeadDays($event->featured_lead_days) !== null => HighlightStartEnum::fromLeadDays($event->featured_lead_days)->value,
        $event?->featured_from !== null => HighlightStartEnum::Custom->value,
        default => HighlightStartEnum::Immediately->value,
    });
    $highlightEnd = old('featured_end', $event?->featured_until ? HighlightEndEnum::Custom->value : HighlightEndEnum::EventStart->value);

    // Wiederholung
    $repeat = old('repeat', RecurrenceEnum::None->value);
    $repeatEnd = old('repeat_end', RecurrenceEndEnum::Count->value);

    $hasError = fn (string ...$fields): string => $errors->hasAny($fields) ? ' has-error' : '';
    $pageTitle = match (true) {
        $isEdit => 'Bearbeiten: '.$event->title,
        $template !== null => 'Erneut ansetzen: '.$template->title,
        default => 'Neue Veranstaltung',
    };
@endphp
<x-layouts.admin :title="$pageTitle">
@if ($template)
    <div class="alert alert-info"><x-icon name="copy" /><span>Alle Angaben von „{{ $template->title }}“ ({{ de_date_short($template->starts_at) }}) sind übernommen – wähle nur noch das neue Datum.</span></div>
@endif
@if ($isEdit && $event->isSeries())
    <div class="alert alert-info series-info">
        <x-icon name="repeat" />
        <span>Dieser Termin gehört zu einer <strong>Serie mit {{ $seriesCount }} Terminen</strong>. Beim Speichern wählst du, ob die Änderungen nur hier oder für weitere Termine gelten.</span>
        <a class="link" href="{{ route('admin.events.index', ['serie' => $event->series_id]) }}">Alle Termine ansehen</a>
    </div>
@endif

    <form class="event-form" method="post" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}" data-event-form novalidate>
        @csrf
@if ($isEdit)
        @method('PUT')
@endif
@if ($template)
        <input type="hidden" name="template_id" value="{{ $template->getKey() }}">
@endif

        <div class="form-main">
            {{-- Art & Genres --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>1</span> Art &amp; Genres</h2>
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

                <div class="field field-highlight{{ $hasError('category_label') }}" data-label-field @if ($category !== EventCategoryEnum::Sonstiges->value) hidden @endif>
                    <label for="category_label">Was für eine Veranstaltung? *</label>
                    <input id="category_label" name="category_label" type="text" maxlength="80"
                           value="{{ old('category_label', $source?->category === EventCategoryEnum::Sonstiges ? $source->category_label : null) }}"
                           list="label-suggestions" placeholder="z. B. Lesung, Comedy, Flohmarkt, Vortrag">
                    <datalist id="label-suggestions">
                        <option value="Lesung"><option value="Comedy"><option value="Vortrag"><option value="Flohmarkt">
                        <option value="Messe"><option value="Theater"><option value="Workshop"><option value="Private Feier">
                    </datalist>
                    <x-admin.field-error name="category_label" />
                </div>

                <div class="field{{ $hasError('genres', 'genres.*', 'new_genres', 'new_genres.*') }}" data-genre-picker>
                    <label for="genre-input">Genres <span class="label-note">mehrere möglich</span></label>
                    <div class="gp-box" data-gp-box>
                        <div class="gp-chips" data-gp-chips></div>
                        <input id="genre-input" class="gp-input" type="text" autocomplete="off" maxlength="60"
                               placeholder="Genre suchen oder neu eingeben …" data-gp-input
                               role="combobox" aria-expanded="false" aria-controls="genre-menu">
                        <button type="button" class="gp-toggle" data-gp-toggle aria-label="Genre-Liste öffnen"><x-icon name="chevron-down" /></button>
                    </div>
                    <div class="gp-menu" id="genre-menu" data-gp-menu>
                        <div class="gp-options" data-gp-options>
@foreach ($genres as $option)
                            <label class="gp-option">
                                <input type="checkbox" name="genres[]" value="{{ $option->getKey() }}" @checked(in_array($option->getKey(), $selectedGenres, true))>
                                <span class="gp-check" aria-hidden="true"><x-icon name="check" /></span>
                                <span class="gp-name">{{ $option->name }}</span>
                                <small>{{ $option->events_count }}</small>
                            </label>
@endforeach
@foreach ((array) old('new_genres', []) as $newGenre)
                            <label class="gp-option is-new">
                                <input type="checkbox" name="new_genres[]" value="{{ $newGenre }}" checked>
                                <span class="gp-check" aria-hidden="true"><x-icon name="check" /></span>
                                <span class="gp-name">{{ $newGenre }}</span>
                                <small>neu</small>
                            </label>
@endforeach
                        </div>
                        <p class="gp-empty" data-gp-empty @if ($genres->isNotEmpty()) hidden @endif>Noch keine Genres – tippe oben eins ein.</p>
                        <button type="button" class="gp-add" data-gp-add hidden><x-icon name="plus" /> <span></span></button>
                    </div>
                    <noscript><input type="text" name="new_genres[]" placeholder="Neues Genre" maxlength="60"></noscript>
                    <x-admin.field-error name="genres" />
                    <x-admin.field-error name="genres.*" />
                    <x-admin.field-error name="new_genres" />
                    <x-admin.field-error name="new_genres.*" />
                </div>
            </section>

            {{-- Basis --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>2</span> Titel &amp; Beschreibung</h2>
                <div class="field{{ $hasError('title') }}">
                    <label for="title">Titel *</label>
                    <input id="title" name="title" type="text" maxlength="200" required value="{{ old('title', $source?->title) }}" placeholder="z. B. Dirty Honey – Live in Bochum" data-preview="title">
                    <x-admin.field-error name="title" />
                </div>
                <div class="field{{ $hasError('subtitle') }}">
                    <label for="subtitle">Untertitel / Support</label>
                    <input id="subtitle" name="subtitle" type="text" maxlength="255" value="{{ old('subtitle', $source?->subtitle) }}" placeholder="z. B. Support: The Band" data-preview="subtitle">
                    <x-admin.field-error name="subtitle" />
                </div>
                <div class="field{{ $hasError('description') }}">
                    <label for="description">Beschreibung</label>
                    <textarea id="description" name="description" rows="7" placeholder="Infos zur Veranstaltung, Line-up, Hinweise …">{{ old('description', $source?->description) }}</textarea>
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
                        <input id="date" name="date" type="date" required value="{{ old('date', $start?->format('Y-m-d')) }}" data-preview="date" @if ($template) autofocus @endif>
                    </div>
                    <div class="field{{ $hasError('date', 'time') }}">
                        <label for="time">Beginn *</label>
                        <input id="time" name="time" type="time" required value="{{ old('time', $source?->starts_at->format('H:i') ?? '20:00') }}" data-preview="time">
                    </div>
                    <div class="field{{ $hasError('doors') }}">
                        <label for="doors">Einlass</label>
                        <input id="doors" name="doors" type="time" value="{{ old('doors', $source?->doors_at ? substr($source->doors_at, 0, 5) : null) }}">
                    </div>
                    <div class="field{{ $hasError('end_time') }}">
                        <label for="end_time">Ende</label>
                        <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $source?->ends_at?->format('H:i')) }}">
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

            {{-- Wiederholung --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>4</span> {{ $isEdit ? 'Weitere Termine anlegen' : 'Wiederholung' }}</h2>
                <p class="hint section-hint">
                    {{ $isEdit
                        ? 'Legt zusätzliche Termine nach diesem an – z. B. für die nächsten Monate. Sie werden mit diesem Termin zu einer Serie verbunden.'
                        : 'Für regelmäßige Partys & Reihen: Jeder Termin wird als eigene Veranstaltung angelegt und kann später einzeln geändert werden.' }}
                </p>
                <div class="grid-2">
                    <div class="field{{ $hasError('repeat') }}">
                        <label for="repeat">Wiederholen</label>
                        <select id="repeat" name="repeat" data-repeat>
@foreach (RecurrenceEnum::cases() as $option)
                            <option value="{{ $option->value }}" @selected($repeat === $option->value)>{{ $option->label() }}</option>
@endforeach
                        </select>
                    </div>
                    <div class="field{{ $hasError('repeat_count', 'repeat_until') }}" data-repeat-end @unless (RecurrenceEnum::tryFrom($repeat)?->isRule()) hidden @endunless>
                        <span class="label">Wie lange?</span>
                        <div class="repeat-end">
                            <div class="segmented">
@foreach (RecurrenceEndEnum::cases() as $option)
                                <label><input type="radio" name="repeat_end" value="{{ $option->value }}" @checked($repeatEnd === $option->value)><span>{{ $option->label() }}</span></label>
@endforeach
                            </div>
                            <input type="number" name="repeat_count" min="2" max="52" value="{{ old('repeat_count', 6) }}" aria-label="Anzahl Termine insgesamt" data-repeat-count @if ($repeatEnd === RecurrenceEndEnum::Until->value) hidden @endif>
                            <input type="date" name="repeat_until" value="{{ old('repeat_until') }}" aria-label="Wiederholen bis" data-repeat-until @unless ($repeatEnd === RecurrenceEndEnum::Until->value) hidden @endunless>
                        </div>
                        <x-admin.field-error name="repeat_count" />
                        <x-admin.field-error name="repeat_until" />
                    </div>
                </div>
                <div class="repeat-custom" data-repeat-custom @unless ($repeat === RecurrenceEnum::Custom->value) hidden @endunless>
                    <input type="date" data-repeat-pick aria-label="Weiteren Termin wählen">
                    <button type="button" class="btn btn-sm" data-repeat-add><x-icon name="plus" /> Termin hinzufügen</button>
                </div>
                <div class="repeat-preview{{ $hasError('repeat', 'repeat_dates', 'repeat_dates.*') }}" data-repeat-preview data-old='@json(array_values((array) old('repeat_dates', [])))' @if ($repeat === RecurrenceEnum::None->value) hidden @endif>
                    <p class="repeat-summary" data-repeat-summary></p>
                    <div class="repeat-dates" data-repeat-dates></div>
                </div>
                <x-admin.field-error name="repeat" />
                <x-admin.field-error name="repeat_dates.*" />
            </section>

            {{-- Veranstalter --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>5</span> Veranstalter</h2>
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

            {{-- Bild & Video --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>6</span> Bild &amp; Video</h2>
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
@if ($imageUrl && $isEdit)
                    <label class="check danger-check"><input type="checkbox" name="remove_image" value="1" data-remove-image> Bild entfernen</label>
@endif
                </div>

                <div class="video-fields">
                    <div class="field{{ $hasError('video_url') }}">
                        <label for="video_url"><x-icon name="play" /> YouTube-Video</label>
                        <input id="video_url" name="video_url" type="url" inputmode="url" maxlength="500" value="{{ old('video_url', $source?->video_url) }}" placeholder="https://www.youtube.com/watch?v=… oder https://youtu.be/…" data-yt-input>
                        <x-admin.field-error name="video_url" />
                        <div class="yt-preview" data-yt-preview hidden>
                            <img alt="" data-yt-thumb>
                            <span class="yt-ok"><x-icon name="check" /> Video erkannt</span>
                        </div>
                    </div>
                    <div class="field{{ $hasError('video') }}">
                        <span class="label"><x-icon name="upload" /> Eigenes Video</span>
@if ($videoMedia)
                        <div class="video-current">
                            <video src="{{ $videoMedia->getUrl() }}" controls preload="metadata" playsinline></video>
@if ($isEdit)
                            <label class="check danger-check"><input type="checkbox" name="remove_video" value="1"> Video entfernen</label>
@endif
                        </div>
@endif
                        <label class="file-pick">
                            <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" data-video-input>
                            <span class="btn btn-sm"><x-icon name="upload" /> {{ $videoMedia ? 'Anderes Video wählen' : 'Video auswählen' }}</span>
                            <span class="file-name" data-video-name>MP4, WebM oder MOV · max. {{ config('matrix.uploads.video_max_kilobytes') / 1024 }} MB</span>
                        </label>
                        <x-admin.field-error name="video" />
                    </div>
                </div>
                <p class="hint">Das Video erscheint auf der Detailseite. YouTube wird erst nach einem Klick geladen (datenschutzfreundlich).</p>
            </section>

            {{-- Tickets --}}
            <section class="panel form-section">
                <h2 class="form-section-title"><span>7</span> Tickets &amp; Einlass</h2>
                <div class="field{{ $hasError('ticket_url') }}">
                    <label for="ticket_url">Ticket-Link</label>
                    <input id="ticket_url" name="ticket_url" type="url" inputmode="url" maxlength="500" value="{{ old('ticket_url', $source?->ticket_url) }}" placeholder="https://…">
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
                        <input id="price_presale" name="price_presale" type="text" maxlength="60" value="{{ old('price_presale', $source?->price_presale) }}" placeholder="z. B. 24 €" data-preview="price">
                        <x-admin.field-error name="price_presale" />
                    </div>
                    <div class="field{{ $hasError('price_box') }}">
                        <label for="price_box">Abendkasse</label>
                        <input id="price_box" name="price_box" type="text" maxlength="60" value="{{ old('price_box', $source?->price_box) }}" placeholder="z. B. 29 €" data-preview="price">
                        <x-admin.field-error name="price_box" />
                    </div>
                    <div class="field{{ $hasError('age_rating') }}">
                        <label for="age_rating">Altersfreigabe</label>
                        <input id="age_rating" name="age_rating" type="text" maxlength="40" value="{{ old('age_rating', $source?->age_rating) }}" placeholder="z. B. ab 18" list="age-suggestions">
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
                <h2 class="form-section-title"><span>8</span> Social Media</h2>
                <p class="hint social-intro">Links zu Künstlern, Bands oder Veranstaltern – dieselbe Plattform darf mehrfach vorkommen.</p>
                <x-admin.social-links-field :links="$source?->social_links ?? []" />
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
                        <input type="checkbox" name="is_featured" value="1" @checked($featured) data-featured-toggle>
                        <span class="switch-ui" aria-hidden="true"></span>
                        <span><strong>Highlight</strong><small>Ganz vorne im Karussell der Startseite</small></span>
                    </label>
                    <div class="featured-window{{ $hasError('featured_from_date', 'featured_from_time', 'featured_until_date', 'featured_until_time') }}" data-featured-window @unless ($featured) hidden @endunless>
                        <div class="field">
                            <label for="featured_start">Highlight ab</label>
                            <select id="featured_start" name="featured_start" data-featured-start>
@foreach (HighlightStartEnum::cases() as $option)
                                <option value="{{ $option->value }}" @selected($highlightStart === $option->value)>{{ $option->label() }}</option>
@endforeach
                            </select>
                            <div class="grid-2" data-featured-from @unless ($highlightStart === HighlightStartEnum::Custom->value) hidden @endunless>
                                <input type="date" name="featured_from_date" value="{{ old('featured_from_date', $event?->featured_from?->format('Y-m-d')) }}" aria-label="Highlight ab Datum">
                                <input type="time" name="featured_from_time" value="{{ old('featured_from_time', $event?->featured_from?->format('H:i') ?? '00:00') }}" aria-label="Highlight ab Uhrzeit">
                            </div>
                        </div>
                        <div class="field">
                            <label for="featured_end">Highlight bis</label>
                            <select id="featured_end" name="featured_end" data-featured-end>
@foreach (HighlightEndEnum::cases() as $option)
                                <option value="{{ $option->value }}" @selected($highlightEnd === $option->value)>{{ $option->label() }}</option>
@endforeach
                            </select>
                            <div class="grid-2" data-featured-until @unless ($highlightEnd === HighlightEndEnum::Custom->value) hidden @endunless>
                                <input type="date" name="featured_until_date" value="{{ old('featured_until_date', $event?->featured_until?->format('Y-m-d')) }}" aria-label="Highlight bis Datum">
                                <input type="time" name="featured_until_time" value="{{ old('featured_until_time', $event?->featured_until?->format('H:i') ?? '23:59') }}" aria-label="Highlight bis Uhrzeit">
                            </div>
                        </div>
                        <p class="hint" data-featured-summary></p>
                        @foreach (['featured_from_date', 'featured_from_time', 'featured_until_date', 'featured_until_time'] as $field)
                        <x-admin.field-error :name="$field" />
                        @endforeach
                    </div>

@if ($isEdit && $event->isSeries())
                    <fieldset class="apply-to">
                        <legend><x-icon name="repeat" /> Änderungen übernehmen für</legend>
@foreach (SeriesScopeEnum::cases() as $option)
                        <label class="radio"><input type="radio" name="apply_to" value="{{ $option->value }}" @checked(old('apply_to', SeriesScopeEnum::Single->value) === $option->value)> {{ $option === SeriesScopeEnum::All ? 'Alle '.$seriesCount.' Termine der Serie' : $option->label() }}</label>
@endforeach
                        <p class="hint">Datum, Ticket-Status, Highlight und geplante Veröffentlichung bleiben je Termin eigenständig.</p>
                    </fieldset>
@endif

                    <div class="save-bar">
                        <button class="btn btn-accent btn-block" type="submit"><x-icon name="check" /> {{ $isEdit ? 'Änderungen speichern' : 'Veranstaltung speichern' }}</button>
@unless ($isEdit)
                        <button class="btn btn-ghost btn-block" type="submit" name="after" value="new">Speichern &amp; weitere anlegen</button>
@endunless
                    </div>
@if ($isEdit)
                    <div class="side-links">
                        <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener"><x-icon name="external" /> Auf der Website ansehen</a>
                        <a href="{{ route('admin.events.create', ['vorlage' => $event->getKey()]) }}"><x-icon name="copy" /> Erneut ansetzen (neuer Termin)</a>
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
                            <span class="pv-genres" data-pv-genres></span>
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
    <form class="danger-zone" method="post" action="{{ route('admin.events.destroy', $event) }}">
        @csrf
        @method('DELETE')
        <div>
            <strong>{{ $event->isSeries() ? 'Termin oder Serie löschen' : 'Veranstaltung löschen' }}</strong>
            <p class="muted">Entfernt dauerhaft samt Bild und Video.</p>
        </div>
        <div class="danger-actions">
@if ($event->isSeries())
@foreach (SeriesScopeEnum::cases() as $option)
            <button class="btn btn-danger" type="submit" name="scope" value="{{ $option->value }}"
                    data-confirm="{{ match ($option) {
                        SeriesScopeEnum::Single => '„'.$event->title.'“ am '.de_date_short($event->starts_at).' wirklich löschen?',
                        SeriesScopeEnum::Following => 'Diesen und alle folgenden Termine der Serie löschen?',
                        SeriesScopeEnum::All => 'Wirklich alle '.$seriesCount.' Termine der Serie löschen?',
                    } }}">@if ($option === SeriesScopeEnum::Single)<x-icon name="trash" /> @endif{{ $option->deleteLabel() }}</button>
@endforeach
@else
            <button class="btn btn-danger" type="submit" data-confirm="„{{ $event->title }}“ wirklich löschen? Das kann nicht rückgängig gemacht werden."><x-icon name="trash" /> Löschen</button>
@endif
        </div>
    </form>
@endif
</x-layouts.admin>
