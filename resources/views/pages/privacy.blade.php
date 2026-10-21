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
            <p>Diese Website lädt keine Schriftarten, Karten oder Tracking-Skripte von Drittanbietern. Schriften werden lokal ausgeliefert. Es wird nur ein technisch notwendiges Sitzungs-Cookie gesetzt, das die Formulare vor Missbrauch schützt und den Login im Administrationsbereich ermöglicht.</p>
            <h2>Kontaktformular, Fundsachen, Bewerbungen und Firmenanfragen</h2>
            {{-- TODO: Speicherdauer und Rechtsgrundlagen vor dem Livegang juristisch prüfen lassen. --}}
            <p>Wenn du uns über ein Formular dieser Website schreibst, speichern wir deine Angaben (z. B. Name, E-Mail-Adresse, Telefonnummer, Nachricht und ggf. hochgeladene Dateien wie Lebenslauf oder Foto), um deine Anfrage zu bearbeiten und dir zu antworten. Die Daten sind nur für unser Team im geschützten Administrationsbereich einsehbar und werden nicht an Dritte weitergegeben.</p>
            <p>Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (Anfrage bzw. Anbahnung eines Vertrags- oder Beschäftigungsverhältnisses) sowie deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO, die du jederzeit per E-Mail widerrufen kannst. Wir löschen die Daten, sobald deine Anfrage erledigt ist und keine gesetzlichen Aufbewahrungspflichten entgegenstehen; Bewerbungsunterlagen spätestens sechs Monate nach Abschluss des Bewerbungsverfahrens.</p>
            <p>Links zu Ticketshops und Kartendiensten führen auf Seiten externer Anbieter; dort gelten deren Datenschutzbestimmungen.</p>
        </div>
    </section>
</x-layouts.site>
