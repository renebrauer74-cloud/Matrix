@props(['event', 'hidden' => false])
@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@inject('describeGenre', 'App\Actions\Events\DescribeEventGenreAction')
@inject('describePrice', 'App\Actions\Events\DescribeEventPriceAction')
@php
    $start = $event->starts_at;
    $genre = $describeGenre->execute($event);
@endphp
<article @class(['event-card', 'cat-'.$event->category->value, 'is-cancelled' => $event->isCancelled()]) data-category="{{ $event->category->value }}" @if ($hidden) hidden @endif>
    <div class="event-card-media">
        <x-events.media :event="$event" />
        <time class="date-badge" datetime="{{ $start->toIso8601String() }}">
            <span class="date-wd">{{ $start->minDayName }}</span>
            <span class="date-day">{{ $start->format('d') }}</span>
            <span class="date-mon">{{ de_month_short($start) }}</span>
        </time>
@if (filled($event->video_url) || $event->hasMedia(\App\Enums\MediaCollectionEnum::EventVideo->value))
        <span class="video-badge" title="Mit Video"><x-icon name="play" /></span>
@endif
@if ($event->ticket_status->showsRibbon())
        <span class="ribbon ribbon-{{ $event->ticket_status->value }}">{{ $event->ticket_status->label() }}</span>
@endif
    </div>

    <div class="event-card-body">
        <div class="event-card-tags">
            <span class="cat-badge">{{ caps($describeBadge->execute($event)) }}</span>
@if ($genre)
            <span class="genre">{{ $genre }}</span>
@endif
        </div>
        <h3 class="event-card-title">
            <a class="stretched-link" href="{{ route('events.show', $event) }}">{{ caps($event->title) }}</a>
        </h3>
@if ($event->subtitle)
        <p class="event-card-sub">{{ $event->subtitle }}</p>
@endif
        <ul class="event-meta">
            <li class="meta-date"><x-icon name="calendar" /> {{ de_date_short($start) }}</li>
            <li><x-icon name="clock" /> {{ $start->format('H:i') }} Uhr @if ($event->doors_at)<span class="muted">· Einlass {{ substr($event->doors_at, 0, 5) }}</span>@endif</li>
@if ($event->rooms)
            <li><x-icon name="pin" /> {{ implode(', ', $event->rooms) }}</li>
@endif
        </ul>
    </div>

    <div class="event-card-foot">
        <span class="price">{{ $describePrice->execute($event) ?? $event->ticket_status->label() }}</span>
@if ($event->canBuyTickets())
        <a class="btn btn-sm btn-accent" href="{{ $event->ticket_url }}" target="_blank" rel="noopener"><x-icon name="ticket" /> Tickets</a>
@else
        <span class="btn btn-sm btn-ghost">Details <x-icon name="arrow-right" /></span>
@endif
    </div>
</article>
