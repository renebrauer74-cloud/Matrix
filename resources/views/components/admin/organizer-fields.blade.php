@props(['organizer' => null, 'prefix' => ''])
@php
    $fields = [
        'name' => ['label' => 'Name / Firma *', 'type' => 'text', 'max' => 150, 'placeholder' => 'z. B. Ruhrpott Concerts GmbH'],
        'contact_person' => ['label' => 'Ansprechpartner', 'type' => 'text', 'max' => 120, 'placeholder' => ''],
        'street' => ['label' => 'Straße und Hausnummer', 'type' => 'text', 'max' => 150, 'placeholder' => ''],
        'zip' => ['label' => 'PLZ', 'type' => 'text', 'max' => 10, 'placeholder' => ''],
        'city' => ['label' => 'Ort', 'type' => 'text', 'max' => 100, 'placeholder' => ''],
        'phone' => ['label' => 'Telefon', 'type' => 'tel', 'max' => 60, 'placeholder' => ''],
        'email' => ['label' => 'E-Mail', 'type' => 'email', 'max' => 190, 'placeholder' => ''],
        'website' => ['label' => 'Website', 'type' => 'url', 'max' => 500, 'placeholder' => 'https://…'],
    ];
@endphp
<div class="grid-2 organizer-grid">
@foreach ($fields as $field => $config)
    <div @class(['field', 'organizer-'.$field, 'has-error' => $errors->has($prefix.$field)])>
        <label for="{{ $prefix.$field }}">{{ $config['label'] }}</label>
        <input id="{{ $prefix.$field }}" name="{{ $prefix.$field }}" type="{{ $config['type'] }}" maxlength="{{ $config['max'] }}"
               value="{{ old($prefix.$field, $organizer?->{$field}) }}" @if ($config['placeholder']) placeholder="{{ $config['placeholder'] }}" @endif data-organizer-field="{{ $field }}">
        <x-admin.field-error :name="$prefix.$field" />
    </div>
@endforeach
</div>
