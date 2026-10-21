@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon']) }} aria-hidden="true"><use href="{{ asset('assets/img/icons.svg') }}#{{ $name }}"></use></svg>
