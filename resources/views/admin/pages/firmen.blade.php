@php
    use App\Enums\MediaCollectionEnum;
    use App\Http\Requests\Admin\PageContentRequest;

    $content = $page->content;
    $value = fn (string $key): mixed => old($key, $content[$key] ?? null);
    $highlights = array_values(old('highlights', $content['highlights'] ?? []));
@endphp
<x-layouts.admin title="Firmenseite bearbeiten">
    <div class="list-toolbar">
        <p class="muted toolbar-note">Die Seite für Firmenkunden unter <a class="link" href="{{ route('corporate') }}" target="_blank" rel="noopener">{{ str_replace(['https://', 'http://'], '', route('corporate')) }}</a>. Anfragen landen unter <a class="link" href="{{ route('admin.messages.index', ['art' => 'corporate']) }}">Nachrichten</a>.</p>
        <div class="toolbar-actions">
            <a class="btn" href="#page-gallery"><x-icon name="grid" /> Bilder</a>
            <a class="btn" href="#page-videos"><x-icon name="stage" /> Videos</a>
        </div>
    </div>

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
            <h2 class="form-section-title"><span>2</span> Vorstellung</h2>
            <div @class(['field', 'has-error' => $errors->has('intro_title')])>
                <label for="intro_title">Zwischenüberschrift</label>
                <input id="intro_title" name="intro_title" type="text" maxlength="120" value="{{ $value('intro_title') }}">
                <x-admin.field-error name="intro_title" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('intro_text')])>
                <label for="intro_text">Text</label>
                <textarea id="intro_text" name="intro_text" rows="7">{{ $value('intro_text') }}</textarea>
                <x-admin.field-error name="intro_text" />
                <p class="hint">Leerzeile = neuer Absatz · <code>**fett**</code> · Das Bild daneben ist das erste Bild der Galerie.</p>
            </div>
        </section>

        <section class="panel form-section">
            <h2 class="form-section-title"><span>3</span> Darum bei uns</h2>
            <p class="hint media-hint">Bis zu {{ PageContentRequest::HIGHLIGHT_SLOTS }} Kacheln. Kacheln ohne Titel werden nicht angezeigt.</p>
            <div class="highlight-grid">
@for ($i = 0; $i < PageContentRequest::HIGHLIGHT_SLOTS; $i++)
                <div class="highlight-slot">
                    <span class="highlight-no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <div @class(['field', 'has-error' => $errors->has("highlights.$i.title")])>
                        <label for="highlight-{{ $i }}-title">Titel</label>
                        <input id="highlight-{{ $i }}-title" name="highlights[{{ $i }}][title]" type="text" maxlength="80" value="{{ $highlights[$i]['title'] ?? '' }}">
                        <x-admin.field-error :name="'highlights.'.$i.'.title'" />
                    </div>
                    <div @class(['field', 'has-error' => $errors->has("highlights.$i.text")])>
                        <label for="highlight-{{ $i }}-text">Text</label>
                        <textarea id="highlight-{{ $i }}-text" name="highlights[{{ $i }}][text]" rows="3" maxlength="300">{{ $highlights[$i]['text'] ?? '' }}</textarea>
                        <x-admin.field-error :name="'highlights.'.$i.'.text'" />
                    </div>
                </div>
@endfor
            </div>
            <div @class(['field', 'has-error' => $errors->has('occasions')])>
                <label for="occasions">Anlässe</label>
                <textarea id="occasions" name="occasions" rows="5">{{ $value('occasions') }}</textarea>
                <x-admin.field-error name="occasions" />
                <p class="hint">Ein Anlass pro Zeile – erscheint als Reihe von Schlagworten.</p>
            </div>
        </section>

        <section class="panel form-section">
            <h2 class="form-section-title"><span>4</span> Anfrage &amp; Kontakt</h2>
            <div class="grid-2">
                <div @class(['field', 'has-error' => $errors->has('cta_title')])>
                    <label for="cta_title">Überschrift über dem Anfrageformular</label>
                    <input id="cta_title" name="cta_title" type="text" maxlength="120" value="{{ $value('cta_title') }}">
                    <x-admin.field-error name="cta_title" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('cta_text')])>
                    <label for="cta_text">Text über dem Anfrageformular</label>
                    <input id="cta_text" name="cta_text" type="text" maxlength="400" value="{{ $value('cta_text') }}">
                    <x-admin.field-error name="cta_text" />
                </div>
            </div>
            <div class="grid-3">
                <div @class(['field', 'has-error' => $errors->has('contact_name')])>
                    <label for="contact_name">Ansprechpartner</label>
                    <input id="contact_name" name="contact_name" type="text" maxlength="120" value="{{ $value('contact_name') }}">
                    <x-admin.field-error name="contact_name" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('contact_phone')])>
                    <label for="contact_phone">Telefon</label>
                    <input id="contact_phone" name="contact_phone" type="text" maxlength="60" value="{{ $value('contact_phone') }}">
                    <x-admin.field-error name="contact_phone" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('contact_email')])>
                    <label for="contact_email">E-Mail</label>
                    <input id="contact_email" name="contact_email" type="email" maxlength="190" value="{{ $value('contact_email') }}">
                    <x-admin.field-error name="contact_email" />
                </div>
            </div>
        </section>

        <div class="page-save">
            <button class="btn btn-accent" type="submit"><x-icon name="check" /> Texte &amp; Titelbild speichern</button>
            <a class="btn btn-ghost" href="{{ route('corporate') }}" target="_blank" rel="noopener"><x-icon name="external" /> Seite ansehen</a>
        </div>
    </form>

    <div class="page-media">
        <x-admin.page-media-manager :page="$page" :page-key="$pageKey" :collection="MediaCollectionEnum::PageGallery" title="Bildergalerie"
            hint="Die Reihenfolge hier ist die Reihenfolge auf der Website – jedes sechste Bild (1., 7., …) wird groß gezeigt. Solange keine Bilder hochgeladen sind, zeigt die Seite die Raumfotos." />
        <x-admin.page-media-manager :page="$page" :page-key="$pageKey" :collection="MediaCollectionEnum::PageVideos" title="Videos"
            hint="Erscheinen unter der Galerie mit Play-Button. Tipp: Videos vorher auf Full HD (1920 px) verkleinern, dann laden sie schneller." />
    </div>
</x-layouts.admin>
