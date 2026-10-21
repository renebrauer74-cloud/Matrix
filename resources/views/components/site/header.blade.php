@php
    $categoryPage = fn (string $slug): bool => request()->routeIs('events.index') && request()->route('kategorie') === $slug;
    $nav = [
        ['url' => route('events.index'), 'label' => 'Programm', 'active' => request()->routeIs('events.index') && ! $categoryPage('konzerte') && ! $categoryPage('partys')],
        ['url' => route('events.index', 'konzerte'), 'label' => 'Konzerte', 'active' => $categoryPage('konzerte')],
        ['url' => route('events.index', 'partys'), 'label' => 'Partys', 'active' => $categoryPage('partys')],
        ['url' => route('location'), 'label' => 'Location', 'active' => request()->routeIs('location')],
        ['url' => route('directions'), 'label' => 'Anfahrt', 'active' => request()->routeIs('directions')],
    ];
@endphp
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="Matrix Bochum – Startseite">
            <img src="{{ asset('assets/img/logo.png') }}" alt="" width="155" height="75">
            <span class="brand-text">Matrix<small>Bochum</small></span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Hauptnavigation">
            <ul>
@foreach ($nav as $item)
                <li><a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
@endforeach
            </ul>
            <a class="btn btn-accent nav-cta" href="{{ config('matrix.venue.tickets_url') }}" target="_blank" rel="noopener">
                <x-icon name="ticket" /> Tickets
            </a>
        </nav>

        <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" data-nav-toggle>
            <span class="sr-only">Menü</span>
            <span class="nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
    </div>
</header>
