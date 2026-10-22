@props(['room', 'conversion' => App\Enums\MediaConversionEnum::Medium])
@php
    use App\Enums\MediaCollectionEnum;

    $src = $room->getFirstMediaUrl(MediaCollectionEnum::RoomImage->value, $conversion->value)
        ?: asset('assets/img/'.$room->key->image());
@endphp
{{-- Foto eines Raums: hochgeladen im Admin-Bereich, sonst das mitgelieferte Foto --}}
<img src="{{ $src }}" alt="{{ $room->key->value }}" {{ $attributes->merge(['loading' => 'lazy']) }}>
