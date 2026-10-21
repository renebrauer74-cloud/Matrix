<x-layouts.site title="Impressum">
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Impressum</h1>
        </div>
    </section>
    <section class="section section-flush">
        <div class="container narrow prose">
            {{-- Angaben von der bisherigen Website übernommen – bitte vor dem Livegang prüfen und vervollständigen. --}}
            <h2>Angaben gemäß § 5 DDG</h2>
            <p>Matrix Gastronomiebetriebe UG (haftungsbeschränkt)<br>
                Hauptstraße 200<br>
                44892 Bochum</p>
            <p>Vertreten durch den Geschäftsführer: Dirk Zimmer</p>
            <h2>Kontakt</h2>
            <p>Telefon: {{ config('matrix.venue.phone') }}<br>
                E-Mail: <a href="mailto:{{ config('matrix.venue.email') }}">{{ config('matrix.venue.email') }}</a></p>
            <h2>Registereintrag</h2>
            <p>Registergericht: Amtsgericht Bochum<br>Registernummer: wird nachgereicht</p>
            <h2>Umsatzsteuer-ID</h2>
            <p>Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: wird nachgereicht</p>
            <h2>Verbraucherstreitbeilegung</h2>
            <p>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
        </div>
    </section>
</x-layouts.site>
