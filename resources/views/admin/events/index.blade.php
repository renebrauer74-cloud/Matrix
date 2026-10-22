@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@inject('describeGenre', 'App\Actions\Events\DescribeEventGenreAction')
@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\Events\EventListFilterEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $listUrl = fn (array $overrides = []): string => route('admin.events.index', array_filter(
        array_merge(['ansicht' => $filter->value, 'kategorie' => $category?->value, 'q' => $search], $overrides),
        fn ($value): bool => filled($value),
    ));
@endphp
<x-layouts.admin title="Veranstaltungen">
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

@if ($events->isEmpty())
    <div class="panel empty">
        <x-icon name="calendar" class="icon icon-xl" />
        <p>{{ $search !== '' || $category ? 'Keine Veranstaltungen für diesen Filter gefunden.' : 'Hier gibt es noch keine Veranstaltungen.' }}</p>
        <a class="btn btn-accent" href="{{ route('admin.events.create') }}"><x-icon name="plus" /> Neue Veranstaltung</a>
    </div>
@else
    <p class="list-count">{{ $events->count() }} {{ $events->count() === 1 ? 'Veranstaltung' : 'Veranstaltungen' }}</p>
    <div class="event-table" role="list">
@foreach ($events as $event)
@php
    $start = $event->starts_at;
    $thumbnail = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value);
    $genre = $describeGenre->execute($event);
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
                    <span class="muted">{{ $genre }}</span>
@endif
@if ($event->rooms)
                    <span class="muted"><x-icon name="pin" /> {{ implode(', ', $event->rooms) }}</span>
@endif
                </div>
            </div>
            <div class="erow-status">
@if ($event->isDraft())
                <span class="tag tag-draft">Entwurf</span>
@else
                <span class="tag tag-live">Online</span>
@endif
@if ($event->is_featured)
                <span class="tag tag-star" title="Highlight auf der Startseite"><x-icon name="star" /></span>
@endif
@if ($event->ticket_status->showsRibbon())
                <span class="tag tag-{{ $event->ticket_status->value }}">{{ $event->ticket_status->label() }}</span>
@endif
            </div>
            <div class="erow-actions">
                <a class="icon-btn" href="{{ route('admin.events.edit', $event) }}" title="Bearbeiten"><x-icon name="edit" /><span class="sr-only">Bearbeiten</span></a>
                <a class="icon-btn" href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" title="Ansehen"><x-icon name="eye" /><span class="sr-only">Ansehen</span></a>
                <details class="menu">
                    <summary class="icon-btn" title="Mehr"><span class="dots" aria-hidden="true">•••</span><span class="sr-only">Mehr Aktionen</span></summary>
                    <div class="menu-pop">
                        <form method="post" action="{{ route('admin.events.status', $event) }}">
                            @csrf
                            <button type="submit"><x-icon :name="$event->isDraft() ? 'eye' : 'eye-off'" /> {{ $event->isDraft() ? 'Veröffentlichen' : 'Offline nehmen' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.events.duplicate', $event) }}">
                            @csrf
                            <button type="submit"><x-icon name="copy" /> Duplizieren</button>
                        </form>
                        <form method="post" action="{{ route('admin.events.destroy', $event) }}" data-confirm="„{{ $event->title }}“ wirklich löschen? Das kann nicht rückgängig gemacht werden.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger"><x-icon name="trash" /> Löschen</button>
                        </form>
                    </div>
                </details>
            </div>
        </article>
@endforeach
    </div>
@endif
</x-layouts.admin>
