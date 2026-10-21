@php
    use App\Enums\Pages\PageEnum;
@endphp
<x-layouts.admin title="Jobs">
    <div class="list-toolbar">
        <p class="muted toolbar-note">Diese Stellen erscheinen auf der <a class="link" href="{{ route('jobs') }}" target="_blank" rel="noopener">Jobs-Seite</a>. Bewerbungen landen unter <a class="link" href="{{ route('admin.messages.index', ['art' => 'application']) }}">Nachrichten</a>.</p>
        <div class="toolbar-actions">
            <a class="btn" href="{{ route('admin.pages.edit', PageEnum::Jobs) }}"><x-icon name="edit" /> Seitentexte</a>
            <a class="btn btn-accent" href="{{ route('admin.job-positions.create') }}"><x-icon name="plus" /> Neue Stelle</a>
        </div>
    </div>

@if ($positions->isEmpty())
    <div class="panel empty">
        <x-icon name="bar" class="icon icon-xl" />
        <p>Noch keine Stellen angelegt – auf der Website ist dann nur die Initiativbewerbung möglich.</p>
        <a class="btn btn-accent" href="{{ route('admin.job-positions.create') }}"><x-icon name="plus" /> Erste Stelle anlegen</a>
    </div>
@else
    <div class="position-list" role="list">
@foreach ($positions as $position)
        <article @class(['prow', 'is-hidden' => ! $position->is_active]) role="listitem">
            <span class="prow-order" title="Reihenfolge">{{ $position->sort_order }}</span>
            <div class="prow-main">
                <a class="prow-title" href="{{ route('admin.job-positions.edit', $position) }}">{{ $position->title }}</a>
                <span class="muted">{{ $position->employment ?: 'Ohne Angabe zur Anstellung' }}@if ($position->summary) · {{ Str::limit($position->summary, 90) }}@endif</span>
            </div>
            <div class="prow-status">
@if ($position->is_active)
                <span class="tag tag-live">Online</span>
@else
                <span class="tag tag-draft">Ausgeblendet</span>
@endif
            </div>
            <div class="erow-actions">
                <a class="icon-btn" href="{{ route('admin.job-positions.edit', $position) }}" title="Bearbeiten"><x-icon name="edit" /><span class="sr-only">Bearbeiten</span></a>
                <details class="menu">
                    <summary class="icon-btn" title="Mehr"><span class="dots" aria-hidden="true">•••</span><span class="sr-only">Mehr Aktionen</span></summary>
                    <div class="menu-pop">
                        <form method="post" action="{{ route('admin.job-positions.toggle', $position) }}">
                            @csrf
                            <button type="submit"><x-icon :name="$position->is_active ? 'eye-off' : 'eye'" /> {{ $position->is_active ? 'Ausblenden' : 'Online stellen' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.job-positions.destroy', $position) }}" data-confirm="„{{ $position->title }}“ wirklich löschen?">
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
