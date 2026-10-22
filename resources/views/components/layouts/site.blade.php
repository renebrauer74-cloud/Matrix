@props([
    'title' => null,
    'metaDescription' => 'Matrix & Rockpalast Bochum: Clubs, Live- & Eventlocation im historischen Müser-Turm in Bochum-Langendreer. Konzerte, Partys und Events.',
    'ogImage' => null,
])
@php
    $pageTitle = $title
        ? $title.' – '.config('app.name')
        : config('app.name').' – '.config('matrix.venue.tagline');
@endphp
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="theme-color" content="#07080a">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="preload" href="{{ asset('assets/fonts/anton-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/site.css') }}">
    <script src="{{ versioned_asset('assets/js/site.js') }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#main">Zum Inhalt springen</a>
    <x-site.header />
    <main id="main">{{ $slot }}</main>
    <x-site.footer />
</body>
</html>
