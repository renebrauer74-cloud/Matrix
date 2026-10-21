<?php
use App\Core\Auth;

$user = Auth::user();
$nav = [
    ['admin', 'Dashboard', 'dashboard', true],
    ['admin/events', 'Veranstaltungen', 'calendar', false],
    ['admin/benutzer', 'Benutzer', 'users', false],
    ['admin/konto', 'Mein Konto', 'user', false],
];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($title ?? 'Admin') . ' – Matrix Admin') ?></title>
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin-body">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="<?= e(url('admin')) ?>">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="" width="46">
            <span>Matrix<small>Admin</small></span>
        </a>
        <a class="btn btn-accent sidebar-new" href="<?= e(url('admin/events/neu')) ?>"><?= icon('plus') ?> Neue Veranstaltung</a>
        <nav class="sidebar-nav" aria-label="Admin-Navigation">
            <?php foreach ($nav as [$path, $label, $ico, $exact]): ?>
                <a href="<?= e(url($path)) ?>"<?= is_active($path, $exact) ? ' aria-current="page"' : '' ?>><?= icon($ico) ?> <?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Website ansehen</a>
            <form method="post" action="<?= e(url('admin/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit"><?= icon('logout') ?> Abmelden</button>
            </form>
            <p class="sidebar-user"><?= icon('user') ?> <?= e($user['name'] ?? '') ?></p>
        </div>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <div class="admin-main">
        <header class="topbar">
            <button class="icon-btn topbar-menu" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false"><?= icon('menu') ?><span class="sr-only">Menü</span></button>
            <h1 class="topbar-title"><?= e($title ?? 'Admin') ?></h1>
            <a class="btn btn-accent btn-sm topbar-new" href="<?= e(url('admin/events/neu')) ?>"><?= icon('plus') ?><span>Neu</span></a>
        </header>

        <div class="admin-content">
            <?php foreach (flash_messages() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>" role="status" data-dismiss>
                    <?= icon($f['type'] === 'error' ? 'alert' : 'check') ?>
                    <span><?= e($f['message']) ?></span>
                    <button type="button" class="alert-close" aria-label="Schließen"><?= icon('close') ?></button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
    </div>
</body>
</html>
