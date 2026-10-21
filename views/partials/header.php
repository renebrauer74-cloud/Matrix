<?php
$nav = [
    ['programm', 'Programm', false],
    ['programm/konzerte', 'Konzerte', true],
    ['programm/partys', 'Partys', true],
    ['location', 'Location', false],
    ['anfahrt', 'Anfahrt', false],
];
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="Matrix Bochum – Startseite">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="" width="155" height="75">
            <span class="brand-text">Matrix<small>Bochum</small></span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Hauptnavigation">
            <ul>
                <?php foreach ($nav as [$path, $label, $exact]): ?>
                    <li><a href="<?= e(url($path)) ?>"<?= is_active($path, $exact) && !($path === 'programm' && (is_active('programm/konzerte') || is_active('programm/partys'))) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-accent nav-cta" href="<?= e(config('venue.tickets_url')) ?>" target="_blank" rel="noopener">
                <?= icon('ticket') ?> Tickets
            </a>
        </nav>

        <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" data-nav-toggle>
            <span class="sr-only">Menü</span>
            <span class="nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
    </div>
</header>
