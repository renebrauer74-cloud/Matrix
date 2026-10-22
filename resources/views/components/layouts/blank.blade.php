@props(['title' => 'Admin'])
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} – {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/admin.css') }}">
</head>
<body class="blank-body">
    {{ $slot }}
</body>
</html>
