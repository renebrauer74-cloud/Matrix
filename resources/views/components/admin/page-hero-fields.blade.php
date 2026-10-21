@props(['page'])
@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $heroImage = $page->getFirstMediaUrl(MediaCollectionEnum::PageHero->value, MediaConversionEnum::Medium->value) ?: null;
    $heroVideo = $page->getFirstMedia(MediaCollectionEnum::PageHeroVideo->value);
@endphp
{{-- Titelbild und Titelvideo – Teil des Seitenformulars --}}
<section class="panel form-section">
    <h2 class="form-section-title"><span><x-icon name="image" /></span> Titelbild &amp; Titelvideo</h2>
    <div class="grid-2">
        <div @class(['field', 'has-error' => $errors->has('hero_image')])>
            <span class="label">Titelbild</span>
@if ($heroImage)
            <img class="media-current" src="{{ $heroImage }}" alt="">
            <label class="check danger-check"><input type="checkbox" name="remove_hero_image" value="1"> Titelbild entfernen</label>
@endif
            <label class="dropzone dropzone-sm" data-upload>
                <span class="dropzone-empty">
                    <x-icon name="upload" class="icon icon-xl" />
                    <strong>{{ $heroImage ? 'Neues Bild wählen' : 'Bild auswählen' }}</strong>
                    <span>Querformat, am besten 2400 px breit · max. {{ config('matrix.uploads.max_kilobytes') / 1024 }} MB</span>
                    <span class="dropzone-names" data-upload-names></span>
                </span>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" aria-label="Titelbild auswählen">
            </label>
            <x-admin.field-error name="hero_image" />
            <p class="hint">Ohne eigenes Bild wird ein Foto des Müser-Turms gezeigt.</p>
        </div>
        <div @class(['field', 'has-error' => $errors->has('hero_video')])>
            <span class="label">Titelvideo (optional)</span>
@if ($heroVideo)
            <video class="media-current" src="{{ $heroVideo->getUrl() }}" muted playsinline controls preload="metadata"></video>
            <label class="check danger-check"><input type="checkbox" name="remove_hero_video" value="1"> Titelvideo entfernen</label>
@endif
            <label class="dropzone dropzone-sm" data-upload>
                <span class="dropzone-empty">
                    <x-icon name="upload" class="icon icon-xl" />
                    <strong>{{ $heroVideo ? 'Neues Video wählen' : 'Video auswählen' }}</strong>
                    <span>MP4 oder WebM · max. {{ config('matrix.uploads.video_max_kilobytes') / 1024 }} MB</span>
                    <span class="dropzone-names" data-upload-names></span>
                </span>
                <input type="file" name="hero_video" accept="video/mp4,video/webm,video/quicktime" aria-label="Titelvideo auswählen">
            </label>
            <x-admin.field-error name="hero_video" />
            <p class="hint">Läuft stumm in Schleife hinter der Überschrift – kurze Clips (10–30 Sek.) ohne Ton wirken am besten. Das Titelbild dient als Vorschaubild.</p>
        </div>
    </div>
</section>
