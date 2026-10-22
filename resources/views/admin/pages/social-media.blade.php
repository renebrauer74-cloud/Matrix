<x-layouts.admin title="Social Media">
    <form class="page-form" method="post" action="{{ route('admin.pages.update', $pageKey) }}">
        @csrf
        @method('PUT')

        <section class="panel form-section">
            <h2 class="form-section-title"><span>1</span> Social-Media-Kanäle der Matrix</h2>
            <p class="hint social-intro">Erscheinen als Symbole im Footer jeder Seite der Website. Reihenfolge wie hier angegeben.</p>
            <x-admin.social-links-field :links="$page->content['social_links'] ?? []" />
        </section>

        <div class="page-save">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> Speichern</button>
            <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external" /> Website ansehen</a>
        </div>
    </form>
</x-layouts.admin>
