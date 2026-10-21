@php
    $content = $page->content;
    $value = fn (string $key): mixed => old($key, $content[$key] ?? null);
@endphp
<x-layouts.admin title="Jobs-Seite bearbeiten">
    <a class="back-link" href="{{ route('admin.job-positions.index') }}"><x-icon name="arrow-left" /> Zurück zu den Stellen</a>

    <form class="page-form" method="post" action="{{ route('admin.pages.update', $pageKey) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="panel form-section">
            <h2 class="form-section-title"><span>1</span> Kopfbereich</h2>
            <div @class(['field', 'has-error' => $errors->has('hero_title')])>
                <label for="hero_title">Überschrift *</label>
                <input id="hero_title" name="hero_title" type="text" maxlength="120" required value="{{ $value('hero_title') }}">
                <x-admin.field-error name="hero_title" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('hero_lead')])>
                <label for="hero_lead">Einleitung</label>
                <textarea id="hero_lead" name="hero_lead" rows="3" maxlength="400">{{ $value('hero_lead') }}</textarea>
                <x-admin.field-error name="hero_lead" />
            </div>
        </section>

        <x-admin.page-hero-fields :page="$page" />

        <section class="panel form-section">
            <h2 class="form-section-title"><span>2</span> Text &amp; Vorteile</h2>
            <div @class(['field', 'has-error' => $errors->has('intro_title')])>
                <label for="intro_title">Zwischenüberschrift</label>
                <input id="intro_title" name="intro_title" type="text" maxlength="120" value="{{ $value('intro_title') }}">
                <x-admin.field-error name="intro_title" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('intro_text')])>
                <label for="intro_text">Text</label>
                <textarea id="intro_text" name="intro_text" rows="7">{{ $value('intro_text') }}</textarea>
                <x-admin.field-error name="intro_text" />
                <p class="hint">Leerzeile = neuer Absatz · <code>**fett**</code> · Links werden automatisch klickbar.</p>
            </div>
            <div @class(['field', 'has-error' => $errors->has('benefits')])>
                <label for="benefits">Das bieten wir</label>
                <textarea id="benefits" name="benefits" rows="5">{{ $value('benefits') }}</textarea>
                <x-admin.field-error name="benefits" />
                <p class="hint">Ein Punkt pro Zeile – erscheint als Liste mit Häkchen.</p>
            </div>
            <div @class(['field', 'has-error' => $errors->has('apply_text')])>
                <label for="apply_text">Hinweis über dem Bewerbungsformular</label>
                <input id="apply_text" name="apply_text" type="text" maxlength="500" value="{{ $value('apply_text') }}">
                <x-admin.field-error name="apply_text" />
            </div>
        </section>

        <div class="page-save">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> Seite speichern</button>
            <a class="btn btn-ghost" href="{{ route('jobs') }}" target="_blank" rel="noopener"><x-icon name="external" /> Seite ansehen</a>
        </div>
    </form>
</x-layouts.admin>
