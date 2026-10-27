@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $visibleEvents = config('matrix.home.visible_events');
    $total = $slides->count();
@endphp
<x-layouts.site>
    {{-- HERO-KARUSSELL --}}
@if ($slides->isNotEmpty())
    <section class="hero" data-carousel data-interval="{{ config('matrix.home.slide_seconds') }}" aria-roledescription="Karussell" aria-label="Aktuelle Veranstaltungen">
        <h1 class="sr-only">Matrix Bochum – aktuelle Veranstaltungen</h1>
        <div class="hero-viewport" data-viewport>
            <div class="hero-track" data-track>
@foreach ($slides as $slide)
@php($background = $slide->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value))
                <div @class(['hero-slide', 'cat-'.$slide->category->value, 'is-active' => $loop->first]) role="group" aria-roledescription="Folie" aria-label="{{ $loop->iteration }} von {{ $total }}: {{ $slide->title }}" @unless ($loop->first) aria-hidden="true" @endunless>
                    <div class="hero-bg" aria-hidden="true">
@if ($background)
                        <img src="{{ $background }}" alt="" draggable="false">
@endif
                    </div>
                    <div class="container hero-inner">
                        <div class="hero-copy">
                            <p class="hero-kicker">
                                <span class="cat-badge">{{ caps($describeBadge->execute($slide)) }}</span>
                                {{ $slide->isFeaturedNow() ? 'Highlight' : 'Demnächst' }}
                            </p>
                            <h2 class="hero-title">{{ caps($slide->title) }}</h2>
@if ($slide->subtitle)
                            <p class="hero-sub">{{ $slide->subtitle }}</p>
@endif
                            <ul class="hero-meta">
                                <li><x-icon name="calendar" /> {{ de_date_long($slide->starts_at) }}</li>
                                <li><x-icon name="clock" /> {{ $slide->starts_at->format('H:i') }} Uhr</li>
@if ($slide->rooms)
                                <li><x-icon name="pin" /> {{ implode(', ', $slide->rooms) }}</li>
@endif
                            </ul>
                            <div class="hero-actions">
@if ($slide->canBuyTickets())
                                <a class="btn btn-accent btn-lg" href="{{ $slide->ticket_url }}" target="_blank" rel="noopener" @unless ($loop->first) tabindex="-1" @endunless><x-icon name="ticket" /> Tickets sichern</a>
@endif
                                <a class="btn btn-outline btn-lg" href="{{ route('events.show', $slide) }}" @unless ($loop->first) tabindex="-1" @endunless>Mehr erfahren <x-icon name="arrow-right" /></a>
                            </div>
                        </div>
                        <a class="hero-poster" href="{{ route('events.show', $slide) }}" tabindex="-1" aria-hidden="true" draggable="false">
                            <x-events.media :event="$slide" sizes="(min-width: 900px) 440px, 90vw" :loading="$loop->first ? 'eager' : 'lazy'" />
@if ($slide->ticket_status->showsRibbon())
                            <span class="ribbon ribbon-{{ $slide->ticket_status->value }}">{{ $slide->ticket_status->label() }}</span>
@endif
                        </a>
                    </div>
                </div>
@endforeach
            </div>
        </div>

@if ($total > 1)
        <div class="hero-bar">
            <div class="container hero-bar-inner">
                <div class="hero-thumbs" role="group" aria-label="Veranstaltung wählen">
@foreach ($slides as $slide)
                    <button type="button" @class(['hero-thumb', 'cat-'.$slide->category->value, 'is-active' => $loop->first]) data-thumb="{{ $loop->index }}" aria-label="{{ $slide->title }}, {{ de_date_short($slide->starts_at) }}" @if ($loop->first) aria-current="true" @endif>
                        <span class="hero-thumb-bar" aria-hidden="true"><i></i></span>
                        <span class="hero-thumb-date">{{ $slide->starts_at->minDayName }} {{ $slide->starts_at->format('d.m.') }}</span>
                        <span class="hero-thumb-title">{{ $slide->title }}</span>
                    </button>
@endforeach
                </div>
                <div class="hero-nav">
                    <span class="hero-counter" aria-hidden="true"><b data-current>01</b> / {{ sprintf('%02d', $total) }}</span>
                    <button type="button" class="hero-arrow" data-prev aria-label="Vorherige Veranstaltung"><x-icon name="arrow-left" /></button>
                    <button type="button" class="hero-arrow" data-next aria-label="Nächste Veranstaltung"><x-icon name="arrow-right" /></button>
                </div>
            </div>
        </div>
@endif
    </section>
@else
    <section class="hero hero-static">
        <div class="hero-slide is-active">
            <div class="hero-bg" aria-hidden="true"><img src="{{ asset('assets/img/aussen.jpg') }}" alt=""></div>
            <div class="container hero-inner hero-inner-single">
                <div class="hero-copy">
                    <p class="hero-kicker">Bochum-Langendreer · seit 1978</p>
                    <h1 class="hero-title">Matrix &amp; Rockpalast</h1>
                    <p class="hero-sub">{{ config('matrix.venue.tagline') }}</p>
                    <div class="hero-actions">
                        <a class="btn btn-accent btn-lg" href="{{ config('matrix.venue.tickets_url') }}" target="_blank" rel="noopener"><x-icon name="ticket" /> Tickets</a>
                        <a class="btn btn-outline btn-lg" href="{{ route('location') }}">Location entdecken</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

    {{-- DEMNÄCHST --}}
    <section class="section" id="programm">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Programm</p>
                    <h2 class="section-title">Demnächst in der Matrix</h2>
                </div>
                <a class="link-arrow hide-sm" href="{{ route('events.index') }}">Gesamtes Programm <x-icon name="arrow-right" /></a>
            </div>

@if ($events->isNotEmpty())
            <div class="toolbar">
                <div class="chips" role="tablist" aria-label="Nach Art filtern" data-filter data-limit="{{ $visibleEvents }}">
                    <button type="button" class="chip is-active" data-filter-value="all" aria-selected="true">Alle <span>{{ $counts['all'] }}</span></button>
@foreach (EventCategoryEnum::cases() as $category)
@if ($counts[$category->value])
                    <button type="button" class="chip chip-{{ $category->value }}" data-filter-value="{{ $category->value }}" aria-selected="false">{{ $category->plural() }} <span>{{ $counts[$category->value] }}</span></button>
@endif
@endforeach
                </div>
                <x-events.view-toggle />
            </div>

            <div class="events" data-events data-view-target>
@foreach ($events as $event)
                <x-events.card :event="$event" :hidden="$loop->index >= $visibleEvents" />
@endforeach
            </div>
            <p class="empty-filter" data-empty hidden>In dieser Kategorie stehen gerade keine Termine an.</p>

            <div class="center-actions">
                <a class="btn btn-outline btn-lg" href="{{ route('events.index') }}" data-more-link data-base="{{ route('events.index') }}">Gesamtes Programm ansehen <x-icon name="arrow-right" /></a>
            </div>
@else
            <div class="empty-state">
                <x-icon name="calendar" class="icon icon-xl" />
                <h3>Neue Termine folgen in Kürze</h3>
                <p>Schau bald wieder vorbei oder sichere dir Tickets direkt im Ticketshop.</p>
            </div>
@endif
        </div>
    </section>

    {{-- TICKETS --}}
    <section class="section section-tight">
        <div class="container">
            <div class="ticket-band">
                <div>
                    <p class="eyebrow">Ticketshop</p>
                    <h2 class="section-title">Sichere dir jetzt dein Ticket</h2>
                    <p>Die heißesten Partys, die besten DJs und Live-Konzerte – die besten Nächte sind schnell ausverkauft. Warte nicht zu lange!</p>
                </div>
                <div class="ticket-band-actions">
                    <a class="btn btn-accent btn-lg" href="{{ config('matrix.venue.tickets_url') }}" target="_blank" rel="noopener"><x-icon name="party" /> Party-Tickets</a>
                    <a class="btn btn-outline btn-lg" href="{{ config('matrix.venue.concert_tickets_url') }}" target="_blank" rel="noopener"><x-icon name="guitar" /> Konzert-Tickets</a>
                </div>
            </div>
        </div>
    </section>

    {{-- LOCATION --}}
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Location</p>
                    <h2 class="section-title">Sechs Räume. Ein Turm.</h2>
                </div>
                <a class="link-arrow hide-sm" href="{{ route('location') }}">Alle Räume <x-icon name="arrow-right" /></a>
            </div>
            <div class="room-rail">
@foreach ($rooms as $room)
                <a class="room-tile" href="{{ route('location') }}#{{ $room->key->anchor() }}">
                    <x-room-image :room="$room" />
                    <span class="room-tile-info">
                        <span class="room-tile-name">{{ $room->key->value }}</span>
@if ($room->capacity)
                        <span class="room-tile-cap"><x-icon name="capacity" /> bis {{ $room->capacity }} Personen</span>
@endif
                    </span>
                </a>
@endforeach
            </div>
        </div>
    </section>

    {{-- GESCHICHTE --}}
    <section class="section">
        <div class="container split">
            <div class="split-media">
                <img src="{{ asset('assets/img/aussen-2.jpg') }}" alt="Der Müser-Turm in Bochum-Langendreer bei Nacht" loading="lazy">
                <span class="split-stamp"><strong>1978</strong>Rock Palast eröffnet</span>
            </div>
            <div class="split-copy">
                <p class="eyebrow">Geschichte</p>
                <h2 class="section-title">Im historischen Müser-Turm</h2>
                <p>1928 entstand das rund 30 Meter hohe Sudhaus der Müser-Brauerei – heute das Herz von Matrix und Rockpalast. Am 1. November 1978 eröffnete hier der Rock Palast und wurde schnell zur überregionalen Adresse für Rock-Fans.</p>
                <p>Seit 1998 trägt das Haus den Namen Matrix, seit 2014 ist die ehemalige Brauerei eine Station der Route der Industriekultur.</p>
                <a class="btn btn-outline" href="{{ route('location') }}">Mehr zur Location <x-icon name="arrow-right" /></a>
            </div>
        </div>
    </section>

    {{-- ANFAHRT --}}
    <section class="section section-tight">
        <div class="container">
            <div class="visit-band">
                <div class="visit-item"><x-icon name="pin" /><div><strong>{{ config('matrix.venue.street') }}</strong><span>{{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}</span></div></div>
                <div class="visit-item"><x-icon name="mail" /><div><strong>Kontakt</strong><a href="mailto:{{ config('matrix.venue.email') }}">{{ config('matrix.venue.email') }}</a></div></div>
                <a class="btn btn-accent" href="{{ config('matrix.venue.route_url') }}" target="_blank" rel="noopener"><x-icon name="route" /> Route planen</a>
            </div>
        </div>
    </section>
</x-layouts.site>
