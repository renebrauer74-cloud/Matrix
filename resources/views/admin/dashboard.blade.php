@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@php
    use App\Enums\Events\EventCategoryEnum;
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend');
    $maxCount = max(1, $counts['all']);
@endphp
<x-layouts.admin title="Dashboard">
    <section class="welcome">
        <div>
            <h2>{{ $greeting }}, {{ Str::before(auth()->user()->name.' ', ' ') }}!</h2>
            <p class="muted">Hier ist der Überblick über das Programm.</p>
        </div>
    </section>

    <div class="stats">
        <a class="stat" href="{{ route('admin.events.index') }}">
            <span class="stat-label"><x-icon name="calendar" /> Kommende Events</span>
            <strong class="stat-value">{{ $stats['upcoming'] }}</strong>
        </a>
        <a class="stat" href="{{ route('admin.events.index', ['ansicht' => 'all']) }}">
            <span class="stat-label"><x-icon name="clock" /> Diesen Monat</span>
            <strong class="stat-value">{{ $stats['month'] }}</strong>
        </a>
        <a class="stat" href="{{ route('admin.events.index', ['ansicht' => 'drafts']) }}">
            <span class="stat-label"><x-icon name="eye-off" /> Entwürfe</span>
            <strong class="stat-value">{{ $stats['drafts'] }}</strong>
        </a>
        <a class="stat" href="{{ route('admin.events.index') }}">
            <span class="stat-label"><x-icon name="ticket" /> Ausverkauft</span>
            <strong class="stat-value">{{ $stats['soldout'] }}</strong>
        </a>
    </div>

    <div class="dash-grid">
        <section class="panel">
            <div class="panel-head">
                <h2>Als Nächstes</h2>
                <a class="link" href="{{ route('admin.events.index') }}">Alle anzeigen</a>
            </div>
@if ($next->isNotEmpty())
            <ul class="mini-list">
@foreach ($next as $event)
@php($thumbnail = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Medium->value))
                <li class="cat-{{ $event->category->value }}">
                    <a href="{{ route('admin.events.edit', $event) }}">
                        <span class="mini-date"><b>{{ $event->starts_at->format('d') }}</b>{{ de_month_short($event->starts_at) }}</span>
                        <span class="mini-thumb">@if ($thumbnail)<img src="{{ $thumbnail }}" alt="" loading="lazy">@endif</span>
                        <span class="mini-body">
                            <strong>{{ $event->title }}</strong>
                            <span><span class="dot"></span>{{ $describeBadge->execute($event) }} · {{ $event->starts_at->minDayName }} {{ $event->starts_at->format('H:i') }}{{ $event->rooms ? ' · '.implode(', ', $event->rooms) : '' }}</span>
                        </span>
@if ($event->isDraft())
                        <span class="tag tag-draft">Entwurf</span>
@elseif ($event->isScheduled())
                        <span class="tag tag-scheduled" title="Geht am {{ $event->publish_at->format('d.m.Y \u\m H:i') }} Uhr online"><x-icon name="clock" /> Geplant</span>
@endif
                    </a>
                </li>
@endforeach
            </ul>
@else
            <div class="empty">
                <p>Noch keine kommenden Veranstaltungen.</p>
                <a class="btn btn-accent" href="{{ route('admin.events.create') }}"><x-icon name="plus" /> Erste Veranstaltung anlegen</a>
            </div>
@endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Nach Art</h2></div>
            <ul class="cat-bars">
@foreach (EventCategoryEnum::cases() as $category)
                <li class="cat-{{ $category->value }}">
                    <a href="{{ route('admin.events.index', ['kategorie' => $category->value]) }}">
                        <span class="cat-bars-label"><span class="dot"></span>{{ $category->plural() }}</span>
                        <strong>{{ $counts[$category->value] }}</strong>
                    </a>
                    <span class="bar"><span style="width: {{ round($counts[$category->value] / $maxCount * 100) }}%"></span></span>
                </li>
@endforeach
            </ul>
            <div class="quick">
@foreach (EventCategoryEnum::cases() as $category)
                <a class="quick-btn cat-{{ $category->value }}" href="{{ route('admin.events.create', ['art' => $category->value]) }}"><x-icon :name="$category->icon()" /> {{ $category->label() }} anlegen</a>
@endforeach
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Offene Nachrichten @if ($messageCounts['all'] > 0)<span class="count">{{ $messageCounts['all'] }}</span>@endif</h2>
                <a class="link" href="{{ route('admin.messages.index') }}">Posteingang</a>
            </div>
@if ($latestMessages->isNotEmpty())
            <ul class="mini-list">
@foreach ($latestMessages as $item)
                <li class="type-{{ $item->type->value }}">
                    <a href="{{ route('admin.messages.show', $item) }}">
                        <span class="mrow-icon"><x-icon :name="$item->type->icon()" /></span>
                        <span class="mini-body">
                            <strong>@if ($item->isUnread())<span class="dot" title="ungelesen"></span> @endif{{ $item->name }}</strong>
                            <span>{{ $item->type->label() }} · {{ $item->subject }}</span>
                        </span>
                        <small class="muted">{{ $item->created_at->isToday() ? $item->created_at->format('H:i') : $item->created_at->format('d.m.') }}</small>
                    </a>
                </li>
@endforeach
            </ul>
@else
            <div class="empty"><p>Keine offenen Nachrichten.</p></div>
@endif
        </section>
    </div>
</x-layouts.admin>
