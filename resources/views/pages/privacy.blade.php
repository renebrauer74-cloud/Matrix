<x-layouts.site title="Datenschutz">
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Datenschutz</h1>
        </div>
    </section>
    <section class="section section-flush">
        <div class="container narrow prose">
            {{-- TODO: Vollständige Datenschutzerklärung einfügen (z. B. über einen Generator erstellen lassen). --}}
            <p class="notice"><x-icon name="alert" /> Die vollständige Datenschutzerklärung wird vor dem Livegang hier eingefügt.</p>
            <h2>Verantwortlicher</h2>
            <p>Matrix Gastronomiebetriebe UG (haftungsbeschränkt), Hauptstraße 200, 44892 Bochum<br>
                E-Mail: <a href="mailto:{{ config('matrix.venue.email') }}">{{ config('matrix.venue.email') }}</a></p>
            <h2>Hinweise zu dieser Website</h2>
            <p>Diese Website lädt keine Schriftarten, Karten oder Tracking-Skripte von Drittanbietern. Schriften werden lokal ausgeliefert. Cookies werden nur im Administrationsbereich (Login-Sitzung) gesetzt.</p>
            <p>Links zu Ticketshops und Kartendiensten führen auf Seiten externer Anbieter; dort gelten deren Datenschutzbestimmungen.</p>
        </div>
    </section>
</x-layouts.site>
