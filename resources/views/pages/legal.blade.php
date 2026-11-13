<x-layouts.site :title="$title">
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">{{ $title }}</h1>
        </div>
    </section>
    <section class="section section-flush">
        <div class="container narrow prose legal-text">
            {{ $body }}
        </div>
    </section>
</x-layouts.site>
