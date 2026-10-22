@props(['title' => 'Admin'])
@inject('countUnreadMessages', 'App\Actions\Messages\CountUnreadMessagesAction')
@php
    use App\Enums\Pages\PageEnum;

    $editedPage = request()->route('page');
    $nav = [
        ['url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['url' => route('admin.events.index'), 'active' => request()->routeIs('admin.events.*'), 'label' => 'Veranstaltungen', 'icon' => 'calendar'],
        ['url' => route('admin.organizers.index'), 'active' => request()->routeIs('admin.organizers.*'), 'label' => 'Veranstalter', 'icon' => 'stage'],
        ['url' => route('admin.messages.index'), 'active' => request()->routeIs('admin.messages.*'), 'label' => 'Nachrichten', 'icon' => 'mail', 'badge' => $countUnreadMessages->execute()],
        ['url' => route('admin.job-positions.index'), 'active' => request()->routeIs('admin.job-positions.*') || $editedPage === PageEnum::Jobs, 'label' => 'Jobs', 'icon' => 'bar'],
        ['url' => route('admin.pages.edit', PageEnum::Corporate), 'active' => $editedPage === PageEnum::Corporate, 'label' => 'Firmenseite', 'icon' => 'capacity'],
        ['url' => route('admin.pages.edit', PageEnum::Social), 'active' => $editedPage === PageEnum::Social, 'label' => 'Social Media', 'icon' => 'instagram'],
        ['url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*'), 'label' => 'Benutzer', 'icon' => 'users'],
        ['url' => route('admin.account.edit'), 'active' => request()->routeIs('admin.account.*'), 'label' => 'Mein Konto', 'icon' => 'user'],
    ];
    $alerts = array_filter([
        'success' => session('success'),
        'error' => session('error') ?? ($errors->any() ? 'Bitte die markierten Felder prüfen.' : null),
    ]);
@endphp
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} – Matrix Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <script src="{{ asset('assets/js/admin.js') }}" defer></script>
</head>
<body class="admin-body">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('assets/img/logo.png') }}" alt="" width="46">
            <span>Matrix<small>Admin</small></span>
        </a>
        <a class="btn btn-accent sidebar-new" href="{{ route('admin.events.create') }}"><x-icon name="plus" /> Neue Veranstaltung</a>
        <nav class="sidebar-nav" aria-label="Admin-Navigation">
@foreach ($nav as $item)
            <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif><x-icon :name="$item['icon']" /> {{ $item['label'] }}@if (! empty($item['badge']))<span class="nav-badge" title="{{ $item['badge'] }} ungelesen">{{ $item['badge'] }}</span>@endif</a>
@endforeach
        </nav>
        <div class="sidebar-foot">
            <a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external" /> Website ansehen</a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"><x-icon name="logout" /> Abmelden</button>
            </form>
            <p class="sidebar-user"><x-icon name="user" /> {{ auth()->user()->name }}</p>
        </div>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <div class="admin-main">
        <header class="topbar">
            <button class="icon-btn topbar-menu" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false"><x-icon name="menu" /><span class="sr-only">Menü</span></button>
            <h1 class="topbar-title">{{ $title }}</h1>
            <a class="btn btn-accent btn-sm topbar-new" href="{{ route('admin.events.create') }}"><x-icon name="plus" /><span>Neu</span></a>
        </header>

        <div class="admin-content">
@foreach ($alerts as $type => $message)
            <div class="alert alert-{{ $type }}" role="status" data-dismiss>
                <x-icon :name="$type === 'error' ? 'alert' : 'check'" />
                <span>{{ $message }}</span>
                <button type="button" class="alert-close" aria-label="Schließen"><x-icon name="close" /></button>
            </div>
@endforeach
            {{ $slot }}
        </div>
    </div>
</body>
</html>
