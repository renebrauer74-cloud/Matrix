@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon']) }} aria-hidden="true"><use href="{{ versioned_asset('assets/img/icons.svg') }}#{{ $name }}"></use></svg>
