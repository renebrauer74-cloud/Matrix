<x-layouts.site title="Seite nicht gefunden">
    <section class="page-hero error-page">
        <div class="container narrow">
            <p class="error-code">404</p>
            <h1 class="page-title">Seite nicht gefunden</h1>
            <p class="page-lead">Diese Seite gibt es nicht (mehr). Vielleicht findest du im Programm, was du suchst.</p>
            <div class="hero-actions">
                <a class="btn btn-accent" href="{{ route('events.index') }}">Zum Programm</a>
                <a class="btn btn-outline" href="{{ route('home') }}">Startseite</a>
            </div>
        </div>
    </section>
</x-layouts.site>
