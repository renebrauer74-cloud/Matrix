@php
    $content = $page->content;
    $history = array_values(array_filter($content['history'] ?? [], fn (array $entry): bool => filled($entry['year'] ?? null) || filled($entry['text'] ?? null)));
@endphp
<x-layouts.site title="Location">
    <x-site.page-hero-media :page="$page" eyebrow="Location" />

    <section class="section">
        <div class="container rooms">
@foreach ($rooms as $room)
            <article @class(['room', 'room-flip' => $loop->even]) id="{{ $room->key->anchor() }}">
                <div class="room-media">
                    <x-room-image :room="$room" :conversion="App\Enums\MediaConversionEnum::Large" />
                </div>
                <div class="room-copy">
@if ($room->capacity)
                    <p class="eyebrow">Bis zu {{ $room->capacity }} Personen</p>
@endif
                    <h2 class="section-title">{{ $room->key->value }}</h2>
@if ($room->description)
                    <p>{{ $room->description }}</p>
@endif
                    <ul class="room-facts">
@if ($room->size)
                        <li><x-icon name="size" /><span>Fläche</span><strong>{{ $room->size }}</strong></li>
@endif
@if ($room->capacity)
                        <li><x-icon name="capacity" /><span>Kapazität</span><strong>{{ $room->capacity }}</strong></li>
@endif
                        <li><x-icon name="bar" /><span>Theken</span><strong>{{ $room->bars }}</strong></li>
@if ($room->stage)
                        <li><x-icon name="stage" /><span>Bühne</span><strong>{{ $room->stage }}</strong></li>
@endif
@if ($room->extra)
                        <li><x-icon name="star" /><span>Extra</span><strong>{{ $room->extra }}</strong></li>
@endif
                    </ul>
                </div>
            </article>
@endforeach
        </div>
    </section>

@if ($history !== [])
    <section class="section">
        <div class="container narrow">
            <p class="eyebrow">Geschichte</p>
@if (filled($content['history_title'] ?? null))
            <h2 class="section-title">{{ $content['history_title'] }}</h2>
@endif
            <ol class="timeline">
@foreach ($history as $entry)
                <li><strong>{{ $entry['year'] }}</strong><p>{{ $entry['text'] }}</p></li>
@endforeach
            </ol>
        </div>
    </section>
@endif

    <section class="section section-tight">
        <div class="container">
            <div class="ticket-band">
                <div>
                    <p class="eyebrow">Eure Veranstaltung</p>
                    <h2 class="section-title">{{ $content['cta_title'] ?: 'Location mieten' }}</h2>
@if (filled($content['cta_text'] ?? null))
                    <p>{{ $content['cta_text'] }}</p>
@endif
                </div>
                <div class="ticket-band-actions">
                    <a class="btn btn-accent btn-lg" href="{{ route('corporate') }}#anfrage"><x-icon name="mail" /> Anfrage senden</a>
                    <a class="btn btn-outline" href="{{ route('corporate') }}">Infos für Firmen</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
