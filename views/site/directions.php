<section class="page-hero">
    <div class="container">
        <p class="eyebrow">Anfahrt</p>
        <h1 class="page-title">So findest du uns</h1>
        <p class="page-lead">Im Müser-Turm in Bochum-Langendreer – nicht zu übersehen.</p>
    </div>
</section>

<section class="section section-flush">
    <div class="container directions">
        <div class="aside-card directions-card">
            <h2>Adresse</h2>
            <p class="directions-address">
                <strong><?= e(config('venue.name')) ?></strong><br>
                <?= e(config('venue.street')) ?><br>
                <?= e(config('venue.zip')) ?> <?= e(config('venue.city')) ?>
            </p>
            <div class="stack">
                <a class="btn btn-accent" href="<?= e(config('venue.route_url')) ?>" target="_blank" rel="noopener"><?= icon('route') ?> Route planen</a>
                <a class="btn btn-outline" href="<?= e(config('venue.maps_url')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> In Google Maps öffnen</a>
            </div>
            <h2>Kontakt</h2>
            <?php if (config('venue.phone')): ?>
                <a class="footer-link" href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) config('venue.phone'))) ?>"><?= icon('phone') ?> <?= e(config('venue.phone')) ?></a>
            <?php endif; ?>
            <a class="footer-link" href="mailto:<?= e(config('venue.email')) ?>"><?= icon('mail') ?> <?= e(config('venue.email')) ?></a>
        </div>
        <div class="directions-media">
            <img src="<?= e(asset('img/aussen.jpg')) ?>" alt="Außenansicht der Matrix Bochum bei Nacht" loading="lazy">
        </div>
    </div>
</section>
