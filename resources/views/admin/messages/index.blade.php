@php
    use App\Enums\Messages\MessageListFilterEnum;
    use App\Enums\Messages\MessageTypeEnum;

    $listUrl = fn (array $overrides = []): string => route('admin.messages.index', array_filter(
        array_merge(['ansicht' => $filter->value, 'art' => $type?->value, 'q' => $search], $overrides),
        fn ($value): bool => filled($value),
    ));
@endphp
<x-layouts.admin title="Nachrichten">
    <div class="list-toolbar">
        <nav class="tabs" aria-label="Status">
@foreach (MessageListFilterEnum::cases() as $option)
            <a href="{{ $listUrl(['ansicht' => $option->value]) }}" @if ($filter === $option) aria-current="page" @endif>
                {{ $option->label() }}@if ($option === MessageListFilterEnum::Open && $counts['all'] > 0) <span class="count">{{ $counts['all'] }}</span>@endif
            </a>
@endforeach
        </nav>
        <form class="list-filter" method="get" action="{{ route('admin.messages.index') }}">
            <input type="hidden" name="ansicht" value="{{ $filter->value }}">
@if ($type)
            <input type="hidden" name="art" value="{{ $type->value }}">
@endif
            <label class="search">
                <x-icon name="search" />
                <span class="sr-only">Suchen</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Name, E-Mail, Text …">
            </label>
        </form>
    </div>

    <nav class="type-chips" aria-label="Art der Nachricht">
        <a href="{{ $listUrl(['art' => null]) }}" @if (! $type) aria-current="page" @endif>Alle Arten</a>
@foreach (MessageTypeEnum::cases() as $option)
        <a class="type-{{ $option->value }}" href="{{ $listUrl(['art' => $option->value]) }}" @if ($type === $option) aria-current="page" @endif>
            <x-icon :name="$option->icon()" /> {{ $option->plural() }}
@if ($counts[$option->value] > 0)
            <span class="count" title="offen">{{ $counts[$option->value] }}</span>
@endif
        </a>
@endforeach
    </nav>

@if ($messages->isEmpty())
    <div class="panel empty">
        <x-icon name="mail" class="icon icon-xl" />
        <p>{{ $search !== '' || $type ? 'Keine Nachrichten für diesen Filter gefunden.' : ($filter === MessageListFilterEnum::Open ? 'Alles erledigt – keine offenen Nachrichten.' : 'Hier gibt es noch keine Nachrichten.') }}</p>
    </div>
@else
    <p class="list-count">{{ $messages->total() }} {{ $messages->total() === 1 ? 'Nachricht' : 'Nachrichten' }}</p>
    <div class="message-list" role="list">
@foreach ($messages as $item)
        <a @class(['mrow', 'type-'.$item->type->value, 'is-unread' => $item->isUnread(), 'is-done' => $item->isDone()]) href="{{ route('admin.messages.show', $item) }}" role="listitem">
            <span class="mrow-icon"><x-icon :name="$item->type->icon()" /></span>
            <span class="mrow-main">
                <span class="mrow-head">
                    <strong>{{ $item->name }}</strong>
                    <span class="pill pill-cat"><span class="dot"></span>{{ $item->type->label() }}</span>
                </span>
                <span class="mrow-subject">{{ $item->subject }}</span>
                <span class="mrow-preview muted">{{ Str::limit((string) $item->body, 140) }}</span>
            </span>
            <span class="mrow-side">
                <time datetime="{{ $item->created_at->toIso8601String() }}" title="{{ $item->created_at->format('d.m.Y H:i') }}">{{ $item->created_at->isToday() ? $item->created_at->format('H:i') : de_date_short($item->created_at) }}</time>
                <span class="mrow-tags">
@if ($item->media_count > 0)
                    <span class="tag" title="Anhang"><x-icon name="upload" /></span>
@endif
@if ($item->replies_count > 0)
                    <span class="tag" title="{{ $item->replies_count }} Antwort(en)"><x-icon name="arrow-left" /> {{ $item->replies_count }}</span>
@endif
                    <span class="tag tag-status-{{ $item->status->value }}">{{ $item->status->label() }}</span>
                </span>
            </span>
        </a>
@endforeach
    </div>

@if ($messages->hasPages())
    <nav class="pager" aria-label="Seiten">
@if ($messages->onFirstPage())
        <span class="btn btn-sm is-disabled"><x-icon name="arrow-left" /> Neuer</span>
@else
        <a class="btn btn-sm" href="{{ $messages->previousPageUrl() }}"><x-icon name="arrow-left" /> Neuer</a>
@endif
        <span class="muted">Seite {{ $messages->currentPage() }} von {{ $messages->lastPage() }}</span>
@if ($messages->hasMorePages())
        <a class="btn btn-sm" href="{{ $messages->nextPageUrl() }}">Älter <x-icon name="arrow-right" /></a>
@else
        <span class="btn btn-sm is-disabled">Älter <x-icon name="arrow-right" /></span>
@endif
    </nav>
@endif
@endif
</x-layouts.admin>
