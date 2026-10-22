@php
    use App\Enums\Location\RoomEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;
    use App\Enums\Messages\CorporateEventTypeEnum;
    use App\Models\Room;

    $content = $page->content;
    $capacities = $rooms->mapWithKeys(fn (Room $room): array => [$room->key->value => $room->capacity]);
    $gallery = $page->getMedia(MediaCollectionEnum::PageGallery->value);
    $videos = $page->getMedia(MediaCollectionEnum::PageVideos->value);
    $occasions = text_lines($content['occasions'] ?? null);
    $highlights = array_values(array_filter($content['highlights'] ?? [], fn (array $highlight): bool => filled($highlight['title'] ?? null)));
    $introImage = $gallery->first()?->getUrl(MediaConversionEnum::Medium->value) ?: asset('assets/img/tube.jpg');

    // Solange keine eigenen Bilder hochgeladen sind, zeigt die Galerie die Raumfotos
    $galleryItems = $gallery->isNotEmpty()
        ? $gallery->map(fn ($media): array => [
            'thumb' => $media->getUrl(MediaConversionEnum::Medium->value),
            'full' => $media->getUrl(MediaConversionEnum::Large->value),
            'alt' => 'Eindruck aus dem Matrix Bochum',
        ])->all()
        : $rooms->map(fn (Room $room): array => [
            'thumb' => $room->getFirstMediaUrl(MediaCollectionEnum::RoomImage->value, MediaConversionEnum::Medium->value) ?: asset('assets/img/'.$room->key->image()),
            'full' => $room->getFirstMediaUrl(MediaCollectionEnum::RoomImage->value, MediaConversionEnum::Large->value) ?: asset('assets/img/'.$room->key->image()),
            'alt' => $room->key->value,
        ])->all();
    $facts = [
        ['value' => $rooms->count(), 'label' => 'Räume & Bühnen'],
        ['value' => (int) $capacities->max(), 'label' => 'Gäste im größten Saal'],
        ['value' => '1928', 'label' => 'historisches Sudhaus'],
        ['value' => '1978', 'label' => 'Livekultur seit'],
    ];
@endphp
<x-layouts.site title="Firmenfeiern & Events" metaDescription="Firmenfeier, Weihnachtsfeier, Sommerfest oder Tagung im Matrix Bochum: sechs Eventräume im historischen Müser-Turm – jetzt unverbindlich anfragen." :ogImage="$introImage">
    <x-site.page-hero-media :page="$page" eyebrow="Für Firmen" large>
        <a class="btn btn-accent btn-lg" href="#anfrage"><x-icon name="mail" /> Unverbindlich anfragen</a>
        <a class="btn btn-outline btn-lg" href="#raeume">Räume entdecken</a>
    </x-site.page-hero-media>

    <section class="section section-tight">
        <div class="container">
            <dl class="fact-band">
@foreach ($facts as $fact)
                <div><dt>{{ $fact['label'] }}</dt><dd>{{ $fact['value'] }}</dd></div>
@endforeach
            </dl>
        </div>
    </section>

    <section class="section">
        <div class="container split">
            <div class="split-media">
                <img src="{{ $introImage }}" alt="" loading="lazy">
            </div>
            <div class="split-copy">
                <p class="eyebrow">Eventlocation im Ruhrgebiet</p>
@if (filled($content['intro_title']))
                <h2 class="section-title">{{ $content['intro_title'] }}</h2>
@endif
                <div class="prose">{{ format_text($content['intro_text']) }}</div>
            </div>
        </div>
    </section>

@if ($highlights !== [])
    <section class="section section-flush">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Darum bei uns</p>
                    <h2 class="section-title">Alles, was euer Event braucht</h2>
                </div>
            </div>
            <div class="feature-grid">
@foreach ($highlights as $highlight)
                <article class="feature">
                    <span class="feature-no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $highlight['title'] }}</h3>
@if (filled($highlight['text'] ?? null))
                    <p>{{ $highlight['text'] }}</p>
@endif
                </article>
@endforeach
            </div>
        </div>
    </section>
@endif

@if ($occasions !== [])
    <section class="section section-tight">
        <div class="container occasions">
            <p class="eyebrow">Anlässe</p>
            <ul>
@foreach ($occasions as $occasion)
                <li>{{ $occasion }}</li>
@endforeach
            </ul>
        </div>
    </section>
@endif

    <section class="section" id="raeume">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Räume</p>
                    <h2 class="section-title">Einzeln oder alles zusammen</h2>
                </div>
                <a class="link-arrow hide-sm" href="{{ route('location') }}">Alle Details <x-icon name="arrow-right" /></a>
            </div>
            <div class="room-rail">
@foreach ($rooms as $room)
                <a class="room-tile" href="{{ route('location') }}#{{ $room->key->anchor() }}">
                    <x-room-image :room="$room" />
                    <span class="room-tile-info">
                        <span class="room-tile-name">{{ $room->key->value }}</span>
                        <span class="room-tile-cap"><x-icon name="capacity" /> {{ implode(' · ', array_filter([$room->capacity ? 'bis '.$room->capacity.' Personen' : null, $room->size])) }}</span>
                    </span>
                </a>
@endforeach
            </div>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Eindrücke</p>
                    <h2 class="section-title">So kann es aussehen</h2>
                </div>
            </div>
            <div class="gallery" data-gallery>
@foreach ($galleryItems as $item)
                <a class="gallery-item" href="{{ $item['full'] }}" data-lightbox>
                    <img src="{{ $item['thumb'] }}" alt="{{ $item['alt'] }}" loading="lazy">
                </a>
@endforeach
            </div>
@if ($videos->isNotEmpty())
            <div class="video-grid">
@foreach ($videos as $video)
                <figure class="video-item">
                    <video src="{{ $video->getUrl() }}" controls playsinline preload="metadata"></video>
                </figure>
@endforeach
            </div>
@endif
        </div>
    </section>

    <section class="section">
        <div class="container form-layout">
            <div class="form-card" id="anfrage">
@if (session('sent'))
                <x-forms.sent title="Anfrage ist da" :again="route('corporate')">Danke für eure Anfrage! Wir melden uns mit Raumvorschlag und Angebot.</x-forms.sent>
@else
                <h2>{{ $content['cta_title'] ?: 'Unverbindlich anfragen' }}</h2>
@if (filled($content['cta_text']))
                <p class="form-intro">{{ $content['cta_text'] }}</p>
@endif
                <x-forms.summary />
                <form class="form-grid" method="post" action="{{ route('corporate.store') }}" novalidate data-site-form>
                    @csrf
                    <x-forms.field name="company" label="Firma" required wide>
                        <input id="f-company" name="company" type="text" maxlength="150" required autocomplete="organization" value="{{ old('company') }}">
                    </x-forms.field>
                    <x-forms.person />
                    <x-forms.field name="event_type" label="Anlass" required>
                        <select id="f-event_type" name="event_type" required>
                            <option value="">Bitte wählen …</option>
@foreach (CorporateEventTypeEnum::cases() as $eventType)
                            <option value="{{ $eventType->value }}" @selected(old('event_type') === $eventType->value)>{{ $eventType->label() }}</option>
@endforeach
                        </select>
                    </x-forms.field>
                    <x-forms.field name="date" label="Wunschtermin">
                        <input id="f-date" name="date" type="date" min="{{ today()->format('Y-m-d') }}" value="{{ old('date') }}">
                    </x-forms.field>
                    <x-forms.field name="guests" label="Anzahl Gäste (ca.)">
                        <input id="f-guests" name="guests" type="number" min="1" max="5000" inputmode="numeric" value="{{ old('guests') }}" placeholder="z. B. 120">
                    </x-forms.field>
                    <x-forms.field name="room" label="Wunschraum" wide>
                        <select id="f-room" name="room">
                            <option value="">Beratet uns gern</option>
@foreach (RoomEnum::cases() as $room)
                            <option value="{{ $room->value }}" @selected(old('room') === $room->value)>{{ $room->value }}{{ ($capacities[$room->value] ?? null) ? ' (bis '.$capacities[$room->value].' Personen)' : '' }}</option>
@endforeach
                        </select>
                    </x-forms.field>
                    <x-forms.field name="body" label="Was habt ihr vor?" wide hint="Ablauf, Catering, Technik, Uhrzeit – alles, was uns bei der Planung hilft.">
                        <textarea id="f-body" name="body" rows="6" maxlength="5000">{{ old('body') }}</textarea>
                    </x-forms.field>
                    <x-forms.consent />
                    <div class="form-actions span-2">
                        <button class="btn btn-accent btn-lg" type="submit"><x-icon name="mail" /> Anfrage senden</button>
                    </div>
                </form>
@endif
            </div>

            <aside class="form-aside">
                <div class="aside-card contact-person">
                    <h2>Euer Kontakt</h2>
@if (filled($content['contact_name']))
                    <p><strong>{{ $content['contact_name'] }}</strong></p>
@endif
@if (filled($content['contact_phone']))
                    <a class="footer-link" href="tel:{{ phone_link($content['contact_phone']) }}"><x-icon name="phone" /> {{ $content['contact_phone'] }}</a>
@endif
@if (filled($content['contact_email']))
                    <a class="footer-link" href="mailto:{{ $content['contact_email'] }}?subject=Firmenanfrage"><x-icon name="mail" /> {{ $content['contact_email'] }}</a>
@endif
                    <a class="footer-link" href="{{ route('directions') }}"><x-icon name="route" /> Anfahrt</a>
                </div>
            </aside>
        </div>
    </section>

    <dialog class="lightbox" data-lightbox-dialog aria-label="Bild vergrößert">
        <button class="lightbox-close" type="button" data-lightbox-close aria-label="Schließen"><x-icon name="close" /></button>
        <button class="lightbox-nav lightbox-prev" type="button" data-lightbox-prev aria-label="Vorheriges Bild"><x-icon name="arrow-left" /></button>
        <img alt="" data-lightbox-img>
        <button class="lightbox-nav lightbox-next" type="button" data-lightbox-next aria-label="Nächstes Bild"><x-icon name="arrow-right" /></button>
    </dialog>
</x-layouts.site>
