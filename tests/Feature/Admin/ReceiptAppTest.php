<?php

it('redirects guests from the receipt app to the login', function (string $path) {
    $this->get(url('admin/quittung'.$path))->assertRedirect(route('admin.login'));
})->with([
    'app' => [''],
    'script' => ['/app.js'],
    'service worker' => ['/sw.js'],
]);

it('shows the receipt app to admins with its base address', function () {
    actingAsAdmin()
        ->get(route('admin.receipts.show'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=utf-8')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<base href="'.route('admin.receipts.show').'/">', false)
        ->assertSee('<meta name="sw-scope" content="/admin/quittung">', false)
        ->assertSee('Quittung erstellen (PDF)')
        ->assertSee('Quittung für Firma');
});

it('serves the app files with the right content type', function (string $path, string $type) {
    actingAsAdmin()
        ->get(route('admin.receipts.file', $path))
        ->assertOk()
        ->assertHeader('Content-Type', $type);
})->with([
    ['app.js', 'text/javascript; charset=utf-8'],
    ['style.css', 'text/css; charset=utf-8'],
    ['vendor/jspdf.umd.min.js', 'text/javascript; charset=utf-8'],
    ['icons/icon-192.png', 'image/png'],
]);

it('lets the service worker control the app address', function () {
    actingAsAdmin()
        ->get(route('admin.receipts.file', 'sw.js'))
        ->assertOk()
        ->assertHeader('Service-Worker-Allowed', '/admin/quittung');
});

it('points the installed app to its admin address', function () {
    $manifest = actingAsAdmin()->get(route('admin.receipts.file', 'manifest.webmanifest'))->assertOk()->json();

    expect($manifest['start_url'])->toBe('/admin/quittung')
        ->and($manifest['scope'])->toBe('/admin/quittung')
        ->and($manifest['name'])->toBe('Quittung');
});

it('serves nothing outside the app files', function (string $path) {
    actingAsAdmin()->get(url('admin/quittung/'.$path))->assertNotFound();
})->with([
    'unknown file' => ['geheim.txt'],
    'path traversal' => ['../../.env'],
    'encoded traversal' => ['..%2F..%2F.env'],
]);

it('links the receipt app in the admin navigation', function () {
    actingAsAdmin()->get(route('admin.dashboard'))->assertSee(route('admin.receipts.show'));
});
