{{-- Bild eines Events – oder ein gestaltetes Ersatz-Cover, falls kein Bild hinterlegt ist. --}}
@props([
    'event',
    'sizes' => '(min-width: 1100px) 360px, (min-width: 700px) 45vw, 100vw',
    'loading' => 'lazy',
])
@inject('describeBadge', 'App\Actions\Events\DescribeEventBadgeAction')
@php
    $media = $event->getFirstMedia(\App\Enums\MediaCollectionEnum::Events->value);
@endphp
@if ($media)
    <img src="{{ $media->getUrl(\App\Enums\MediaConversionEnum::Medium->value) }}"
         srcset="{{ $media->getUrl(\App\Enums\MediaConversionEnum::Medium->value) }} 760w, {{ $media->getUrl(\App\Enums\MediaConversionEnum::Large->value) }} 1600w"
         sizes="{{ $sizes }}" alt="{{ $event->title }}" loading="{{ $loading }}" decoding="async"
         style="object-position: {{ $event->image_focus->objectPosition() }}">
@else
    <div class="cover-fallback" aria-hidden="true">
        <img class="cover-logo" src="{{ asset('assets/img/logo.png') }}" alt="" loading="lazy">
        <span class="cover-kicker">{{ caps($describeBadge->execute($event)) }}</span>
        <span class="cover-title">{{ caps($event->title) }}</span>
    </div>
@endif
