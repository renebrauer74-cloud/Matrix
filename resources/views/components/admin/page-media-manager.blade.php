@props(['page', 'pageKey', 'collection', 'title', 'hint' => null])
@php
    use App\Enums\MediaConversionEnum;

    $items = $page->getMedia($collection->value);
    $isVideo = $collection->holdsVideos();
    $maxMegabytes = config($isVideo ? 'matrix.uploads.video_max_kilobytes' : 'matrix.uploads.max_kilobytes') / 1024;
@endphp
{{-- Galerie bzw. Videoliste: eigene Formulare zum Hochladen, Sortieren und Löschen --}}
<section class="panel form-section" id="{{ $collection->value }}">
    <div class="panel-head">
        <h2 class="form-section-title"><span><x-icon :name="$isVideo ? 'stage' : 'grid'" /></span> {{ $title }}</h2>
        <span class="muted">{{ $items->count() }} {{ $isVideo ? ($items->count() === 1 ? 'Video' : 'Videos') : ($items->count() === 1 ? 'Bild' : 'Bilder') }}</span>
    </div>
@if ($hint)
    <p class="hint media-hint">{{ $hint }}</p>
@endif

@if ($items->isNotEmpty())
    <ul @class(['media-grid', 'media-grid-video' => $isVideo])>
@foreach ($items as $item)
        <li class="media-tile">
@if ($isVideo)
            <video src="{{ $item->getUrl() }}" muted playsinline controls preload="metadata"></video>
@else
            <img src="{{ $item->getUrl(MediaConversionEnum::Medium->value) }}" alt="" loading="lazy">
@endif
            <div class="media-tile-bar">
                <span class="media-tile-name" title="{{ $item->file_name }}">{{ $loop->iteration }}. {{ $item->file_name }}</span>
                <div class="media-tile-actions">
@unless ($loop->first)
                    <form method="post" action="{{ route('admin.pages.media.move', [$pageKey, $item->id]) }}">
                        @csrf
                        <input type="hidden" name="richtung" value="vor">
                        <button class="icon-btn" type="submit" title="Nach vorne"><x-icon name="arrow-left" /><span class="sr-only">Nach vorne</span></button>
                    </form>
@endunless
@unless ($loop->last)
                    <form method="post" action="{{ route('admin.pages.media.move', [$pageKey, $item->id]) }}">
                        @csrf
                        <input type="hidden" name="richtung" value="zurueck">
                        <button class="icon-btn" type="submit" title="Nach hinten"><x-icon name="arrow-right" /><span class="sr-only">Nach hinten</span></button>
                    </form>
@endunless
                    <form method="post" action="{{ route('admin.pages.media.destroy', [$pageKey, $item->id]) }}" data-confirm="„{{ $item->file_name }}“ wirklich löschen?">
                        @csrf
                        @method('DELETE')
                        <button class="icon-btn icon-btn-danger" type="submit" title="Löschen"><x-icon name="trash" /><span class="sr-only">Löschen</span></button>
                    </form>
                </div>
            </div>
        </li>
@endforeach
    </ul>
@endif

    <form class="media-upload" method="post" action="{{ route('admin.pages.media.store', $pageKey) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="collection" value="{{ $collection->value }}">
        <label @class(['dropzone', 'dropzone-sm', 'has-error' => old('collection') === $collection->value && $errors->hasAny(['files', 'files.*'])]) data-upload>
            <span class="dropzone-empty">
                <x-icon name="upload" class="icon icon-xl" />
                <strong>{{ $isVideo ? 'Videos' : 'Bilder' }} hierher ziehen oder <u>auswählen</u></strong>
                <span>{{ $isVideo ? 'MP4, WebM oder MOV' : 'JPG, PNG, WebP' }} · mehrere auf einmal möglich · max. {{ $maxMegabytes }} MB pro Datei</span>
                <span class="dropzone-names" data-upload-names></span>
            </span>
            <input type="file" name="files[]" multiple required accept="{{ $isVideo ? 'video/mp4,video/webm,video/quicktime' : 'image/jpeg,image/png,image/webp,image/gif,image/avif' }}" aria-label="Dateien auswählen">
        </label>
@if (old('collection') === $collection->value)
        <x-admin.field-error name="files" />
@foreach ($errors->get('files.*') as $fileErrors)
        <p class="field-error">{{ $fileErrors[0] }}</p>
@endforeach
@endif
        <button class="btn btn-accent" type="submit"><x-icon name="upload" /> Hochladen</button>
    </form>
</section>
