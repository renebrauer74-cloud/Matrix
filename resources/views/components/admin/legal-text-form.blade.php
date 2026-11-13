@props(['pageKey', 'page'])
<form class="page-form" method="post" action="{{ route('admin.pages.update', $pageKey) }}">
    @csrf
    @method('PUT')

    <nav class="legal-switch" aria-label="Rechtstexte">
@foreach ([\App\Enums\Pages\PageEnum::Imprint, \App\Enums\Pages\PageEnum::Privacy] as $legalPage)
        <a @class(['btn', 'btn-sm', 'btn-accent' => $legalPage === $pageKey, 'btn-ghost' => $legalPage !== $pageKey]) href="{{ route('admin.pages.edit', $legalPage) }}" @if ($legalPage === $pageKey) aria-current="page" @endif>{{ $legalPage->label() }}</a>
@endforeach
    </nav>

    <section class="panel form-section">
        <h2 class="form-section-title"><span>1</span> Text der Seite „{{ $pageKey->label() }}“</h2>
        <div @class(['field', 'has-error' => $errors->has('body')])>
            <label for="body">Text *</label>
            <textarea id="body" name="body" class="legal-textarea" rows="24" required spellcheck="true">{{ old('body', $page->content['body'] ?? '') }}</textarea>
            <x-admin.field-error name="body" />
        </div>
        <div class="legal-help">
            <p class="hint"><strong>So wird formatiert:</strong></p>
            <ul class="hint">
                <li><code>## Überschrift</code> – Zeile mit zwei Rauten wird eine Zwischenüberschrift</li>
                <li>Leerzeile – neuer Absatz; ein einfacher Zeilenumbruch bleibt als Umbruch erhalten (z. B. für Adressen)</li>
                <li><code>**fett**</code> – fett gedruckt · <code>- Punkt</code> – Aufzählung</li>
                <li>Web-Adressen und E-Mail-Adressen werden automatisch klickbar</li>
            </ul>
            <p class="hint">Text aus einem Datenschutz-Generator kann direkt eingefügt werden – danach am besten die Überschriften mit <code>##</code> markieren.</p>
        </div>
    </section>

    <div class="page-save">
        <button class="btn btn-accent" type="submit"><x-icon name="check" /> Speichern</button>
        <a class="btn btn-ghost" href="{{ route($pageKey->routeName()) }}" target="_blank" rel="noopener"><x-icon name="external" /> Seite ansehen</a>
    </div>
</form>
