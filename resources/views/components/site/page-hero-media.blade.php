@props(['page', 'eyebrow', 'fallbackImage' => 'aussen.jpg', 'large' => false])
@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $heroImage = $page->getFirstMediaUrl(MediaCollectionEnum::PageHero->value, MediaConversionEnum::Large->value) ?: asset('assets/img/'.$fallbackImage);
    $heroVideo = $page->getFirstMedia(MediaCollectionEnum::PageHeroVideo->value);
@endphp
{{-- Seitenkopf mit Titelbild aus dem Admin-Bereich, optional mit stummem Hintergrundvideo --}}
<section @class(['page-hero', 'page-hero-image', 'page-hero-xl' => $large])>
    <div class="hero-bg" aria-hidden="true">
@if ($heroVideo)
        <video src="{{ $heroVideo->getUrl() }}" poster="{{ $heroImage }}" autoplay muted loop playsinline preload="metadata"></video>
@else
        <img src="{{ $heroImage }}" alt="">
@endif
    </div>
    <div class="container">
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1 class="page-title">{{ $page->content['hero_title'] }}</h1>
@if (filled($page->content['hero_lead']))
        <p class="page-lead">{{ $page->content['hero_lead'] }}</p>
@endif
@if ($slot->isNotEmpty())
        <div class="hero-actions page-hero-actions">{{ $slot }}</div>
@endif
    </div>
</section>
