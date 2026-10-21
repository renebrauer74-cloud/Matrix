<x-layouts.site title="Location">
    <section class="page-hero page-hero-image">
        <div class="hero-bg" aria-hidden="true"><img src="{{ asset('assets/img/aussen.jpg') }}" alt=""></div>
        <div class="container">
            <p class="eyebrow">Location</p>
            <h1 class="page-title">Matrix &amp; Rockpalast</h1>
            <p class="page-lead">Unsere beiden Clubs, Live- &amp; Eventlocations im historischen Müser-Turm in Bochum-Langendreer.</p>
        </div>
    </section>

    <section class="section">
        <div class="container rooms">
@foreach (App\Enums\Location\RoomEnum::spaces() as $room)
            <article @class(['room', 'room-flip' => $loop->even]) id="{{ $room->anchor() }}">
                <div class="room-media">
                    <img src="{{ asset('assets/img/'.$room->image()) }}" alt="{{ $room->value }}" loading="lazy">
                </div>
                <div class="room-copy">
                    <p class="eyebrow">Bis zu {{ $room->capacity() }} Personen</p>
                    <h2 class="section-title">{{ $room->value }}</h2>
                    <p>{{ $room->description() }}</p>
                    <ul class="room-facts">
                        <li><x-icon name="size" /><span>Fläche</span><strong>{{ $room->size() }}</strong></li>
                        <li><x-icon name="capacity" /><span>Kapazität</span><strong>{{ $room->capacity() }}</strong></li>
                        <li><x-icon name="bar" /><span>Theken</span><strong>{{ $room->bars() }}</strong></li>
@if ($room->stage())
                        <li><x-icon name="stage" /><span>Bühne</span><strong>{{ $room->stage() }}</strong></li>
@endif
@if ($room->extra())
                        <li><x-icon name="star" /><span>Extra</span><strong>{{ $room->extra() }}</strong></li>
@endif
                    </ul>
                </div>
            </article>
@endforeach
        </div>
    </section>

    <section class="section">
        <div class="container narrow">
            <p class="eyebrow">Geschichte</p>
            <h2 class="section-title">Vom Sudhaus zum Club</h2>
            <ol class="timeline">
                <li><strong>1806</strong><p>Johann Wilhelm Müser gründet eine Brauerei, Schankwirtschaft und einen Laden an der Hauptstraße in Langendreer.</p></li>
                <li><strong>1891</strong><p>Daraus entsteht die Bierbrauerei Gebrüder Müser AG – der Ausbau zur Großbrauerei folgt.</p></li>
                <li><strong>1928</strong><p>Letzte Erweiterung: das rund 30 Meter hohe Sudhaus – heute das Herz von Rockpalast und Matrix.</p></li>
                <li><strong>1976</strong><p>Die Schultheiss-Brauerei, seit 1960 Eigentümerin, schließt das traditionsreiche Brauhaus.</p></li>
                <li><strong>1978</strong><p>Am 1. November eröffnet der Rock Palast im 1. Obergeschoss – schnell eine überregionale Adresse für Rock-Fans.</p></li>
                <li><strong>1996</strong><p>Ausbau zur mehrräumigen Veranstaltungslocation, seit 1998 unter dem Namen Matrix.</p></li>
                <li><strong>2014</strong><p>Der Rockpalast wird eigenständige Live- &amp; Eventvenue. Die ehemalige Brauerei wird Station der Route der Industriekultur.</p></li>
            </ol>
        </div>
    </section>

    <section class="section section-tight">
        <div class="container">
            <div class="ticket-band">
                <div>
                    <p class="eyebrow">Eure Veranstaltung</p>
                    <h2 class="section-title">Location mieten</h2>
                    <p>Konzert, Firmenfeier, Hochzeit oder Lesung – sprecht uns an, wir finden den passenden Raum.</p>
                </div>
                <div class="ticket-band-actions">
                    <a class="btn btn-accent btn-lg" href="mailto:{{ config('matrix.venue.email') }}?subject=Anfrage%20Location"><x-icon name="mail" /> Anfrage senden</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
