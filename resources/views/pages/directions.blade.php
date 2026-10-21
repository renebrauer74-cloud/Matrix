<x-layouts.site title="Anfahrt">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Anfahrt</p>
            <h1 class="page-title">So findest du uns</h1>
            <p class="page-lead">Im Müser-Turm in Bochum-Langendreer – nicht zu übersehen.</p>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container directions">
            <div class="aside-card directions-card">
                <h2>Adresse</h2>
                <p class="directions-address">
                    <strong>{{ config('matrix.venue.name') }}</strong><br>
                    {{ config('matrix.venue.street') }}<br>
                    {{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}
                </p>
                <div class="stack">
                    <a class="btn btn-accent" href="{{ config('matrix.venue.route_url') }}" target="_blank" rel="noopener"><x-icon name="route" /> Route planen</a>
                    <a class="btn btn-outline" href="{{ config('matrix.venue.maps_url') }}" target="_blank" rel="noopener"><x-icon name="external" /> In Google Maps öffnen</a>
                </div>
                <h2>Kontakt</h2>
                <x-site.contact-links />
            </div>
            <div class="directions-media">
                <img src="{{ asset('assets/img/aussen.jpg') }}" alt="Außenansicht der Matrix Bochum bei Nacht" loading="lazy">
            </div>
        </div>
    </section>
</x-layouts.site>
