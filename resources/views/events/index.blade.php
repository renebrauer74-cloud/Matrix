@php
    use App\Enums\Events\EventCategoryEnum;

    $filterQuery = array_filter(['raum' => $room, 'genre' => $genre?->slug]);
    $categoryUrl = fn (?EventCategoryEnum $category): string => route('events.index', array_filter(['kategorie' => $category?->slug()] + $filterQuery));
    $heading = $genre?->name ?? $category?->plural() ?? 'Alle Veranstaltungen';
@endphp
<x-layouts.site :title="$genre?->name ?? $category?->plural() ?? 'Programm'">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Programm</p>
            <h1 class="page-title">{{ caps($heading) }}</h1>
            <p class="page-lead">
                {{ $total }} {{ $total === 1 ? 'Termin' : 'Termine' }}{{ $genre && $category ? ' · '.$category->plural() : '' }}{{ $room ? ' im Raum '.$room : '' }} – Konzerte, Partys und mehr im Müser-Turm.
@if ($genre || $room)
                <a class="reset-filter" href="{{ route('events.index', array_filter(['kategorie' => $category?->slug()])) }}">Filter zurücksetzen</a>
@endif
            </p>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container">
            <div class="toolbar toolbar-sticky">
                <nav class="chips" aria-label="Nach Art filtern">
                    <a @class(['chip', 'is-active' => ! $category]) href="{{ $categoryUrl(null) }}" @if (! $category) aria-current="page" @endif>Alle <span>{{ $counts['all'] }}</span></a>
@foreach (EventCategoryEnum::cases() as $option)
                    <a @class(['chip', 'chip-'.$option->value, 'is-active' => $category === $option]) href="{{ $categoryUrl($option) }}" @if ($category === $option) aria-current="page" @endif>{{ $option->plural() }} <span>{{ $counts[$option->value] }}</span></a>
@endforeach
                </nav>
                <div class="toolbar-right">
@if ($rooms->isNotEmpty() || $genres->isNotEmpty())
                    <form class="room-filter" method="get" action="{{ route('events.index', array_filter(['kategorie' => $category?->slug()])) }}" data-autosubmit>
@if ($genres->isNotEmpty())
                        <label class="sr-only" for="genre">Genre</label>
                        <select id="genre" name="genre">
                            <option value="">Alle Genres</option>
@foreach ($genres as $option)
                            <option value="{{ $option->slug }}" @selected($genre?->is($option))>{{ $option->name }}</option>
@endforeach
                        </select>
@endif
@if ($rooms->isNotEmpty())
                        <label class="sr-only" for="raum">Raum</label>
                        <select id="raum" name="raum">
                            <option value="">Alle Räume</option>
@foreach ($rooms as $option)
                            <option value="{{ $option }}" @selected($option === $room)>{{ $option }}</option>
@endforeach
                        </select>
@endif
                        <noscript><button class="btn btn-sm" type="submit">Filtern</button></noscript>
                    </form>
@endif
                    <x-events.view-toggle />
                </div>
            </div>

@if ($months->isEmpty())
            <div class="empty-state">
                <x-icon name="calendar" class="icon icon-xl" />
                <h3>Aktuell keine Termine</h3>
                <p>Hier stehen gerade keine Veranstaltungen an. Schau bald wieder vorbei!</p>
@if ($category || $room || $genre)
                <a class="btn btn-outline" href="{{ route('events.index') }}">Alle Veranstaltungen zeigen</a>
@endif
            </div>
@endif

@foreach ($months as $key => $monthEvents)
            <section class="month" aria-labelledby="m-{{ $key }}">
                <h2 class="month-title" id="m-{{ $key }}">
                    {{ $monthEvents->first()->starts_at->translatedFormat('F Y') }} <span>{{ $monthEvents->count() }}</span>
                </h2>
                <div class="events" data-view-target>
@foreach ($monthEvents as $event)
                    <x-events.card :event="$event" />
@endforeach
                </div>
            </section>
@endforeach
        </div>
    </section>
</x-layouts.site>
