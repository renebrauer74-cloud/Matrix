@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@inject('describeGenre', 'App\Actions\Events\DescribeEventGenreAction')
@inject('describePrice', 'App\Actions\Events\DescribeEventPriceAction')
@inject('summarize', 'App\Actions\Events\SummarizeEventDescriptionAction')
@inject('buildStructuredData', 'App\Actions\Events\BuildEventStructuredDataAction')
@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $start = $event->starts_at;
    $end = $event->ends_at;
    $genre = $describeGenre->execute($event);
    $price = $describePrice->execute($event);
    $background = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value);
    $ogImage = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Large->value);
@endphp
<x-layouts.site :title="$event->title" :meta-description="$summarize->execute($event, 155) ?: ($event->subtitle ?? '')" :og-image="$ogImage ?: null">
    <script type="application/ld+json">{!! json_encode($buildStructuredData->execute($event), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

@if ($event->isDraft())
    <div class="draft-bar">
        <x-icon name="eye-off" /> Entwurf – nur für eingeloggte Admins sichtbar.
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
                            <span class="cat-badge">{{ $describeBadge->execute($event) }}</span>
@if ($genre)
                            <span class="genre">{{ $genre }}</span>
@endif
                        </div>
                        <h1 class="event-title">{{ $event->title }}</h1>
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
                    </div>
                </div>
            </div>
        </div>

        <div class="container event-body">
            <div class="prose">
@if ($event->description)
                {{ format_text($event->description) }}
@else
                <p class="muted">Weitere Infos folgen.</p>
@endif
            </div>
            <aside class="event-aside">
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
