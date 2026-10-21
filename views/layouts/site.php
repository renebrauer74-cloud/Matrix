<?php
/** @var string $content */
$pageTitle = isset($title) ? $title . ' – ' . config('app.name') : config('app.name') . ' – ' . config('venue.tagline');
$desc = $metaDescription ?? 'Matrix & Rockpalast Bochum: Clubs, Live- & Eventlocation im historischen Müser-Turm in Bochum-Langendreer. Konzerte, Partys und Events.';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <meta name="theme-color" content="#07080a">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/anton-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <script src="<?= e(asset('js/site.js')) ?>" defer></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
    <a class="skip-link" href="#main">Zum Inhalt springen</a>
    <?= partial('partials/header') ?>
    <main id="main"><?= $content ?></main>
    <?= partial('partials/footer') ?>
</body>
</html>
