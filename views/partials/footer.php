<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="Matrix Bochum" width="110" height="53" loading="lazy">
            <p>Matrix &amp; Rockpalast Bochum – zwei Clubs, Live- &amp; Eventlocations im historischen Müser-Turm.</p>
            <div class="footer-social">
                <?php if (config('venue.instagram')): ?>
                    <a href="<?= e(config('venue.instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram') ?></a>
                <?php endif; ?>
                <?php if (config('venue.facebook')): ?>
                    <a href="<?= e(config('venue.facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <h2 class="footer-title">Adresse</h2>
            <address>
                <?= e(config('venue.name')) ?><br>
                <?= e(config('venue.street')) ?><br>
                <?= e(config('venue.zip')) ?> <?= e(config('venue.city')) ?>
            </address>
            <a class="footer-link" href="<?= e(config('venue.route_url')) ?>" target="_blank" rel="noopener"><?= icon('route') ?> Route planen</a>
        </div>
        <div>
            <h2 class="footer-title">Kontakt</h2>
            <?php if (config('venue.phone')): ?>
                <a class="footer-link" href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) config('venue.phone'))) ?>"><?= icon('phone') ?> <?= e(config('venue.phone')) ?></a>
            <?php endif; ?>
            <a class="footer-link" href="mailto:<?= e(config('venue.email')) ?>"><?= icon('mail') ?> <?= e(config('venue.email')) ?></a>
            <a class="footer-link" href="<?= e(config('venue.tickets_url')) ?>" target="_blank" rel="noopener"><?= icon('ticket') ?> Ticketshop</a>
        </div>
        <div>
            <h2 class="footer-title">Entdecken</h2>
            <ul class="footer-nav">
                <li><a href="<?= e(url('programm')) ?>">Programm</a></li>
                <li><a href="<?= e(url('programm/konzerte')) ?>">Konzerte</a></li>
                <li><a href="<?= e(url('programm/partys')) ?>">Partys</a></li>
                <li><a href="<?= e(url('location')) ?>">Location</a></li>
                <li><a href="<?= e(url('jobs')) ?>">Jobs</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> Matrix Bochum</span>
        <nav aria-label="Rechtliches">
            <a href="<?= e(url('impressum')) ?>">Impressum</a>
            <a href="<?= e(url('datenschutz')) ?>">Datenschutz</a>
        </nav>
    </div>
</footer>
