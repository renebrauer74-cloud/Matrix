@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@inject('describeGenre', 'App\Actions\Events\DescribeEventGenreAction')
@inject('describeHighlight', 'App\Actions\Events\DescribeHighlightWindowAction')
@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\Events\EventListFilterEnum;
    use App\Enums\Events\SeriesScopeEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $listUrl = fn (array $overrides = []): string => route('admin.events.index', array_filter(
        array_merge(['ansicht' => $filter->value, 'kategorie' => $category?->value, 'q' => $search], $overrides),
        fn ($value): bool => filled($value),
    ));
@endphp
<x-layouts.admin :title="$seriesId ? 'Serie' : 'Veranstaltungen'">
@if ($seriesId)
    <div class="series-head">
        <strong><x-icon name="repeat" /> Serie „{{ $events->first()?->title }}“ · {{ $events->count() }} Termine</strong>
        <a class="link" href="{{ route('admin.events.index') }}">← Alle Veranstaltungen</a>
    </div>
@else
    <div class="list-toolbar">
        <nav class="tabs" aria-label="Zeitraum">
@foreach (EventListFilterEnum::cases() as $option)
            <a href="{{ $listUrl(['ansicht' => $option->value]) }}" @if ($filter === $option) aria-current="page" @endif>{{ $option->label() }}</a>
@endforeach
        </nav>
        <form class="list-filter" method="get" action="{{ route('admin.events.index') }}">
            <input type="hidden" name="ansicht" value="{{ $filter->value }}">
            <label class="search">
                <x-icon name="search" />
                <span class="sr-only">Suchen</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Titel, Raum, Genre …">
            </label>
            <select name="kategorie" aria-label="Art" onchange="this.form.submit()">
                <option value="">Alle Arten</option>
@foreach (EventCategoryEnum::cases() as $option)
                <option value="{{ $option->value }}" @selected($category === $option)>{{ $option->plural() }}</option>
@endforeach
            </select>
        </form>
    </div>
@endif

@if ($events->isEmpty())
    <div class="panel empty">
        <x-icon name="calendar" class="icon icon-xl" />
        <p>{{ $search !== '' || $category ? 'Keine Veranstaltungen für diesen Filter gefunden.' : 'Hier gibt es noch keine Veranstaltungen.' }}</p>
        <a class="btn btn-accent" href="{{ route('admin.events.create') }}"><x-icon name="plus" /> Neue Veranstaltung</a>
    </div>
@else
@if ($filter === EventListFilterEnum::Past && ! $seriesId)
    <p class="list-count">Tipp: Mit <strong>„Erneut ansetzen“</strong> legst du eine vergangene Veranstaltung mit allen Angaben neu an – du wählst nur das neue Datum.</p>
@else
    <p class="list-count">{{ $events->count() }} {{ $events->count() === 1 ? 'Veranstaltung' : 'Veranstaltungen' }}</p>
@endif
    <div class="event-table" role="list">
@foreach ($events as $event)
@php
    $start = $event->starts_at;
    $thumbnail = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value);
    $genre = $describeGenre->execute($event, 3);
    $hasVideo = filled($event->video_url) || $event->hasMedia(MediaCollectionEnum::EventVideo->value);
@endphp
        <article @class(['erow', 'cat-'.$event->category->value, 'is-past' => $event->isPast()]) role="listitem">
            <a class="erow-thumb" href="{{ route('admin.events.edit', $event) }}" tabindex="-1" aria-hidden="true">
@if ($thumbnail)
                <img src="{{ $thumbnail }}" alt="" loading="lazy">
@else
                <span class="erow-noimg"><x-icon name="image" /></span>
@endif
            </a>
            <div class="erow-date">
                <b>{{ $start->format('d') }}</b>
                <span>{{ de_month_short($start) }} {{ $start->format('y') }}</span>
                <small>{{ $start->minDayName }} {{ $start->format('H:i') }}</small>
            </div>
            <div class="erow-main">
                <a class="erow-title" href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a>
                <div class="erow-meta">
                    <span class="erow-date-inline">{{ de_date_short($start) }} · {{ $start->format('H:i') }}</span>
                    <span class="pill pill-cat"><span class="dot"></span>{{ $describeBadge->execute($event) }}</span>
@if ($genre)
                    <span class="erow-genres">{{ $genre }}</span>
@endif
@if ($event->rooms)
                    <span class="muted"><x-icon name="pin" /> {{ implode(', ', $event->rooms) }}</span>
@endif
                </div>
            </div>
            <div class="erow-status">
@if ($event->isDraft())
                <span class="tag tag-draft">Entwurf</span>
@elseif ($event->isScheduled())
                <span class="tag tag-scheduled" title="Geht am {{ $event->publish_at->format('d.m.Y \u\m H:i') }} Uhr online"><x-icon name="clock" /> {{ $event->publish_at->format('d.m. H:i') }}</span>
@else
                <span class="tag tag-live">Online</span>
@endif
@if ($event->isSeries() && ! $seriesId)
                <a class="tag tag-series" href="{{ route('admin.events.index', ['serie' => $event->series_id]) }}" title="Alle Termine dieser Serie"><x-icon name="repeat" /> {{ $event->series_events_count }}</a>
@endif
@if ($event->is_featured)
                <span class="tag tag-star" title="{{ $describeHighlight->execute($event) }}"><x-icon name="star" />{{ $event->isFeaturedNow() ? '' : ' geplant' }}</span>
@endif
@if ($hasVideo)
                <span class="tag tag-video" title="Mit Video"><x-icon name="play" /></span>
@endif
@if ($event->ticket_status->showsRibbon())
                <span class="tag tag-{{ $event->ticket_status->value }}">{{ $event->ticket_status->label() }}</span>
@endif
            </div>
            <div class="erow-actions">
                <a class="icon-btn" href="{{ route('admin.events.edit', $event) }}" title="Bearbeiten"><x-icon name="edit" /><span class="sr-only">Bearbeiten</span></a>
                <a class="icon-btn btn-again" href="{{ route('admin.events.create', ['vorlage' => $event->getKey()]) }}" title="Erneut ansetzen"><x-icon name="copy" /><span class="sr-only">Erneut ansetzen</span></a>
                <details class="menu">
                    <summary class="icon-btn" title="Mehr"><span class="dots" aria-hidden="true">•••</span><span class="sr-only">Mehr Aktionen</span></summary>
                    <div class="menu-pop">
                        <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener"><x-icon name="eye" /> Auf Website ansehen</a>
                        <a href="{{ route('admin.events.create', ['vorlage' => $event->getKey()]) }}"><x-icon name="repeat" /> Erneut ansetzen</a>
                        <form method="post" action="{{ route('admin.events.status', $event) }}">
                            @csrf
                            <button type="submit"><x-icon :name="$event->isOnline() ? 'eye-off' : 'eye'" /> {{ $event->isOnline() ? 'Offline nehmen' : ($event->isScheduled() ? 'Sofort veröffentlichen' : 'Veröffentlichen') }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.events.duplicate', $event) }}">
                            @csrf
                            <button type="submit"><x-icon name="copy" /> Duplizieren</button>
                        </form>
                        <div class="menu-sep"></div>
                        <form method="post" action="{{ route('admin.events.destroy', $event) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger" name="scope" value="{{ SeriesScopeEnum::Single->value }}" data-confirm="„{{ $event->title }}“ am {{ de_date_short($event->starts_at) }} wirklich löschen? Das kann nicht rückgängig gemacht werden."><x-icon name="trash" /> {{ $event->isSeries() ? 'Nur diesen Termin löschen' : 'Löschen' }}</button>
@if ($event->isSeries())
                            <button type="submit" class="danger" name="scope" value="{{ SeriesScopeEnum::Following->value }}" data-confirm="Diesen und alle folgenden Termine der Serie „{{ $event->title }}“ löschen?"><x-icon name="trash" /> Serie ab hier löschen</button>
@endif
                        </form>
                    </div>
                </details>
            </div>
        </article>
@endforeach
    </div>
@endif
</x-layouts.admin>
