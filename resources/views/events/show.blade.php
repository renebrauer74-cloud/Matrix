@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@inject('describePrice', 'App\Actions\Events\DescribeEventPriceAction')
@inject('extractYoutubeId', 'App\Actions\Events\ExtractYoutubeIdAction')
@inject('summarize', 'App\Actions\Events\SummarizeEventDescriptionAction')
@inject('buildStructuredData', 'App\Actions\Events\BuildEventStructuredDataAction')
@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $start = $event->starts_at;
    $end = $event->ends_at;
    $price = $describePrice->execute($event);
    $background = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value);
    $ogImage = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Large->value);
    $video = $event->getFirstMedia(MediaCollectionEnum::EventVideo->value);
    $youtubeId = $extractYoutubeId->execute($event->video_url);
@endphp
<x-layouts.site :title="$event->title" :meta-description="$summarize->execute($event, 155) ?: ($event->subtitle ?? '')" :og-image="$ogImage ?: null">
    <script type="application/ld+json">{!! json_encode($buildStructuredData->execute($event), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

@if ($event->isDraft())
    <div class="draft-bar">
        <x-icon name="eye-off" /> Entwurf – nur für eingeloggte Admins sichtbar.
        <a href="{{ route('admin.events.edit', $event) }}">Bearbeiten</a>
    </div>
@elseif ($event->isScheduled())
    <div class="draft-bar">
        <x-icon name="clock" /> Geplant – geht am {{ $event->publish_at->format('d.m.Y \u\m H:i') }} Uhr online, bis dahin nur für eingeloggte Admins sichtbar.
        <a href="{{ route('admin.events.edit', $event) }}">Bearbeiten</a>
    </div>
@endif

    <article class="event-detail cat-{{ $event->category->value }}">
        <div class="event-hero">
            <div class="hero-bg" aria-hidden="true">
@if ($background)
                <img src="{{ $background }}" alt="">
@endif
            </div>
            <div class="container event-hero-inner">
                <a class="back-link" href="{{ route('events.index') }}"><x-icon name="arrow-left" /> Zum Programm</a>

                <div class="event-hero-grid">
                    <div class="event-poster">
                        <x-events.media :event="$event" sizes="(min-width: 900px) 520px, 100vw" loading="eager" />
@if ($event->ticket_status->showsRibbon())
                        <span class="ribbon ribbon-{{ $event->ticket_status->value }}">{{ $event->ticket_status->label() }}</span>
@endif
                    </div>

                    <div class="event-head">
                        <div class="event-card-tags">
                            <span class="cat-badge">{{ caps($describeBadge->execute($event)) }}</span>
@foreach ($event->genres as $genre)
                            <a class="genre-tag" href="{{ route('events.index', ['genre' => $genre->slug]) }}">{{ $genre->name }}</a>
@endforeach
                        </div>
                        <h1 class="event-title">{{ caps($event->title) }}</h1>
@if ($event->subtitle)
                        <p class="event-subtitle">{{ $event->subtitle }}</p>
@endif

                        <dl class="facts">
                            <div><dt><x-icon name="calendar" /> Datum</dt><dd>{{ de_date_long($start) }}</dd></div>
                            <div><dt><x-icon name="clock" /> Beginn</dt><dd>{{ $start->format('H:i') }} Uhr{{ $end ? ' – '.$end->format('H:i').' Uhr' : '' }}</dd></div>
@if ($event->doors_at)
                            <div><dt><x-icon name="door" /> Einlass</dt><dd>{{ substr($event->doors_at, 0, 5) }} Uhr</dd></div>
@endif
                            <div><dt><x-icon name="pin" /> Ort</dt><dd>{{ $event->rooms ? implode(', ', $event->rooms) : config('matrix.venue.name') }}</dd></div>
@if ($price)
                            <div><dt><x-icon name="ticket" /> Preis</dt><dd>{{ $price }}</dd></div>
@endif
@if ($event->age_rating)
                            <div><dt><x-icon name="user" /> Einlass</dt><dd>{{ $event->age_rating }}</dd></div>
@endif
                            <div><dt><x-icon name="users" /> Veranstalter</dt><dd>{{ $event->organizer?->name ?? config('matrix.venue.name') }}</dd></div>
                        </dl>

                        <div class="event-actions">
@if ($event->canBuyTickets())
                            <a class="btn btn-accent btn-lg" href="{{ $event->ticket_url }}" target="_blank" rel="noopener"><x-icon name="ticket" /> Tickets kaufen</a>
@else
                            <span class="status-pill status-{{ $event->ticket_status->value }}">{{ $event->ticket_status->label() }}</span>
@endif
                            <a class="btn btn-outline" href="{{ route('events.calendar', $event) }}"><x-icon name="calendar" /> In Kalender</a>
                            <button class="btn btn-outline" type="button" data-share data-title="{{ $event->title }}" data-url="{{ route('events.show', $event) }}"><x-icon name="share" /> Teilen</button>
                        </div>
                        <x-site.social-links class="event-social" :links="$event->social_links" labelled />

                    </div>
                </div>
            </div>
        </div>

        <div class="container event-body">
            <div class="event-main">
@if ($video)
                <div class="event-video">
                    <video controls playsinline preload="metadata" @if ($ogImage) poster="{{ $ogImage }}" @endif>
                        <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                    </video>
                </div>
@endif
@if ($youtubeId)
                <div class="event-video yt-facade" data-yt="{{ $youtubeId }}" data-title="{{ $event->title }}">
@if ($ogImage)
                    <img src="{{ $ogImage }}" alt="" loading="lazy">
@endif
                    <button type="button" class="yt-play" aria-label="Video auf YouTube abspielen"><x-icon name="play" /></button>
                    <p class="yt-note"><x-icon name="alert" /> Beim Abspielen werden Daten an YouTube (Google) übertragen.</p>
                </div>
@endif
                <div class="prose">
@if ($event->description)
                    {{ format_text($event->description) }}
@else
                    <p class="muted">Weitere Infos folgen.</p>
@endif
                </div>
            </div>
            <aside class="event-aside">
@if ($seriesDates->isNotEmpty())
                <div class="aside-card series-card">
                    <h2>Weitere Termine</h2>
                    <ul class="series-dates">
@foreach ($seriesDates as $date)
                        <li>
                            <a href="{{ route('events.show', $date) }}">
                                <span class="sd-day"><b>{{ $date->starts_at->format('d') }}</b>{{ de_month_short($date->starts_at) }}</span>
                                <span class="sd-info"><strong>{{ $date->starts_at->dayName }}</strong>{{ $date->starts_at->format('H:i') }} Uhr{{ $date->ticket_status->showsRibbon() ? ' · '.$date->ticket_status->label() : '' }}</span>
                                <x-icon name="arrow-right" />
                            </a>
                        </li>
@endforeach
                    </ul>
                </div>
@endif
                <div class="aside-card">
                    <h2>Location</h2>
                    <p><strong>{{ config('matrix.venue.name') }}</strong>{{ $event->rooms ? ' · '.implode(', ', $event->rooms) : '' }}<br>
                        {{ config('matrix.venue.street') }}, {{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}</p>
                    <a class="link-arrow" href="{{ config('matrix.venue.route_url') }}" target="_blank" rel="noopener">Route planen <x-icon name="arrow-right" /></a>
                </div>
            </aside>
        </div>
    </article>

@if ($related->isNotEmpty())
    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2 class="section-title">Das könnte dir auch gefallen</h2>
                <a class="link-arrow hide-sm" href="{{ route('events.index') }}">Gesamtes Programm <x-icon name="arrow-right" /></a>
            </div>
            <div class="events events-3">
@foreach ($related as $relatedEvent)
                <x-events.card :event="$relatedEvent" />
@endforeach
            </div>
        </div>
    </section>
@endif
</x-layouts.site>
