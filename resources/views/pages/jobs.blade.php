<x-layouts.site title="Jobs">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Jobs</p>
            <h1 class="page-title">Werde Teil der Crew</h1>
            <p class="page-lead">Theke, Garderobe, Security, Technik oder Veranstaltung – wir suchen immer Leute, die die Nacht lieben.</p>
        </div>
    </section>
    <section class="section section-flush">
        <div class="container narrow">
            <div class="aside-card">
                <h2>Initiativ bewerben</h2>
                <p>Schick uns ein paar Zeilen über dich, deine Erfahrung und wann du Zeit hast.</p>
                <a class="btn btn-accent" href="mailto:{{ config('matrix.venue.jobs_email') }}?subject=Bewerbung"><x-icon name="mail" /> {{ config('matrix.venue.jobs_email') }}</a>
            </div>
        </div>
    </section>
</x-layouts.site>
