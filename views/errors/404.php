<section class="page-hero error-page">
    <div class="container narrow">
        <p class="error-code">404</p>
        <h1 class="page-title"><?= e($title ?? 'Seite nicht gefunden') ?></h1>
        <p class="page-lead">Diese Seite gibt es nicht (mehr). Vielleicht findest du im Programm, was du suchst.</p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="<?= e(url('programm')) ?>">Zum Programm</a>
            <a class="btn btn-outline" href="<?= e(url('/')) ?>">Startseite</a>
        </div>
    </div>
</section>
