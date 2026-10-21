<?php
/**
 * @var App\Models\Event[] $slides
 * @var App\Models\Event[] $events
 * @var array $counts
 */
use App\Controllers\PageController;
use App\Models\Event;

$homeLimit = 9;
$total = count($slides);
?>
<!-- HERO-KARUSSELL ---------------------------------------------------------->
<?php if ($slides): ?>
<section class="hero" data-carousel aria-roledescription="Karussell" aria-label="Aktuelle Veranstaltungen">
    <h1 class="sr-only">Matrix Bochum – aktuelle Veranstaltungen</h1>
    <div class="hero-viewport" data-viewport>
        <div class="hero-track" data-track>
            <?php foreach ($slides as $i => $f): $s = $f->start(); ?>
                <div class="hero-slide cat-<?= e($f->category) ?><?= $i === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="Folie" aria-label="<?= $i + 1 ?> von <?= $total ?>: <?= e($f->title) ?>"<?= $i ? ' aria-hidden="true"' : '' ?>>
                    <div class="hero-bg" aria-hidden="true">
                        <?php if ($bg = $f->imageUrl('md')): ?><img src="<?= e($bg) ?>" alt="" draggable="false"><?php endif; ?>
                    </div>
                    <div class="container hero-inner">
                        <div class="hero-copy">
                            <p class="hero-kicker">
                                <span class="cat-badge"><?= e($f->badge()) ?></span>
                                <?= $f->is_featured ? 'Highlight' : 'Demnächst' ?>
                            </p>
                            <h2 class="hero-title"><?= e($f->title) ?></h2>
                            <?php if ($f->subtitle): ?><p class="hero-sub"><?= e($f->subtitle) ?></p><?php endif; ?>
                            <ul class="hero-meta">
                                <li><?= icon('calendar') ?> <?= e(de_date_long($s)) ?></li>
                                <li><?= icon('clock') ?> <?= e($s->format('H:i')) ?> Uhr</li>
                                <?php if ($f->room): ?><li><?= icon('pin') ?> <?= e($f->room) ?></li><?php endif; ?>
                            </ul>
                            <div class="hero-actions">
                                <?php if ($f->canBuyTickets()): ?>
                                    <a class="btn btn-accent btn-lg" href="<?= e($f->ticket_url) ?>" target="_blank" rel="noopener"<?= $i ? ' tabindex="-1"' : '' ?>><?= icon('ticket') ?> Tickets sichern</a>
                                <?php endif; ?>
                                <a class="btn btn-outline btn-lg" href="<?= e($f->url()) ?>"<?= $i ? ' tabindex="-1"' : '' ?>>Mehr erfahren <?= icon('arrow-right') ?></a>
                            </div>
                        </div>
                        <a class="hero-poster" href="<?= e($f->url()) ?>" tabindex="-1" aria-hidden="true" draggable="false">
                            <?= partial('partials/event-media', ['event' => $f, 'sizes' => '(min-width: 900px) 440px, 90vw', 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
                            <?php if ($r = $f->ribbon()): ?><span class="ribbon ribbon-<?= e($f->ticket_status) ?>"><?= e($r) ?></span><?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($total > 1): ?>
        <div class="hero-bar">
            <div class="container hero-bar-inner">
                <div class="hero-thumbs" role="group" aria-label="Veranstaltung wählen">
                    <?php foreach ($slides as $i => $f): $s = $f->start(); ?>
                        <button type="button" class="hero-thumb cat-<?= e($f->category) ?><?= $i === 0 ? ' is-active' : '' ?>" data-thumb="<?= $i ?>" aria-label="<?= e($f->title) ?>, <?= e(de_date_short($s)) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                            <span class="hero-thumb-bar" aria-hidden="true"><i></i></span>
                            <span class="hero-thumb-date"><?= e(de_weekday($s, true)) ?> <?= e($s->format('d.m.')) ?></span>
                            <span class="hero-thumb-title"><?= e($f->title) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="hero-nav">
                    <span class="hero-counter" aria-hidden="true"><b data-current>01</b> / <?= sprintf('%02d', $total) ?></span>
                    <button type="button" class="hero-arrow" data-prev aria-label="Vorherige Veranstaltung"><?= icon('arrow-left') ?></button>
                    <button type="button" class="hero-arrow" data-next aria-label="Nächste Veranstaltung"><?= icon('arrow-right') ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="hero hero-static">
    <div class="hero-slide is-active">
        <div class="hero-bg" aria-hidden="true"><img src="<?= e(asset('img/aussen.jpg')) ?>" alt=""></div>
        <div class="container hero-inner hero-inner-single">
            <div class="hero-copy">
                <p class="hero-kicker">Bochum-Langendreer · seit 1978</p>
                <h1 class="hero-title">Matrix &amp; Rockpalast</h1>
                <p class="hero-sub"><?= e(config('venue.tagline')) ?></p>
                <div class="hero-actions">
                    <a class="btn btn-accent btn-lg" href="<?= e(config('venue.tickets_url')) ?>" target="_blank" rel="noopener"><?= icon('ticket') ?> Tickets</a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('location')) ?>">Location entdecken</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- DEMNÄCHST -------------------------------------------------------------->
<section class="section" id="programm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Programm</p>
                <h2 class="section-title">Demnächst in der Matrix</h2>
            </div>
            <a class="link-arrow hide-sm" href="<?= e(url('programm')) ?>">Gesamtes Programm <?= icon('arrow-right') ?></a>
        </div>

        <?php if ($events): ?>
            <div class="toolbar">
                <div class="chips" role="tablist" aria-label="Nach Art filtern" data-filter data-limit="<?= $homeLimit ?>">
                    <button type="button" class="chip is-active" data-filter-value="all" aria-selected="true">Alle <span><?= $counts['all'] ?></span></button>
                    <?php foreach (Event::CATEGORIES as $key => $c): if (!$counts[$key]) continue; ?>
                        <button type="button" class="chip chip-<?= e($key) ?>" data-filter-value="<?= e($key) ?>" aria-selected="false"><?= e($c['plural']) ?> <span><?= $counts[$key] ?></span></button>
                    <?php endforeach; ?>
                </div>
                <?= partial('partials/view-toggle') ?>
            </div>

            <div class="events" data-events data-view-target>
                <?php foreach ($events as $i => $event): ?>
                    <?= partial('partials/event-card', ['event' => $event, 'hidden' => $i >= $homeLimit]) ?>
                <?php endforeach; ?>
            </div>
            <p class="empty-filter" data-empty hidden>In dieser Kategorie stehen gerade keine Termine an.</p>

            <div class="center-actions">
                <a class="btn btn-outline btn-lg" href="<?= e(url('programm')) ?>" data-more-link data-base="<?= e(url('programm')) ?>">Gesamtes Programm ansehen <?= icon('arrow-right') ?></a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('calendar', 'icon icon-xl') ?>
                <h3>Neue Termine folgen in Kürze</h3>
                <p>Schau bald wieder vorbei oder sichere dir Tickets direkt im Ticketshop.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- TICKETS ------------------------------------------------------------------->
<section class="section section-tight">
    <div class="container">
        <div class="ticket-band">
            <div>
                <p class="eyebrow">Ticketshop</p>
                <h2 class="section-title">Sichere dir jetzt dein Ticket</h2>
                <p>Die heißesten Partys, die besten DJs und Live-Konzerte – die besten Nächte sind schnell ausverkauft. Warte nicht zu lange!</p>
            </div>
            <div class="ticket-band-actions">
                <a class="btn btn-accent btn-lg" href="<?= e(config('venue.tickets_url')) ?>" target="_blank" rel="noopener"><?= icon('party') ?> Party-Tickets</a>
                <a class="btn btn-outline btn-lg" href="<?= e(config('venue.concert_tickets_url')) ?>" target="_blank" rel="noopener"><?= icon('guitar') ?> Konzert-Tickets</a>
            </div>
        </div>
    </div>
</section>

<!-- LOCATION ------------------------------------------------------------------>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Location</p>
                <h2 class="section-title">Sechs Räume. Ein Turm.</h2>
            </div>
            <a class="link-arrow hide-sm" href="<?= e(url('location')) ?>">Alle Räume <?= icon('arrow-right') ?></a>
        </div>
        <div class="room-rail">
            <?php foreach (PageController::ROOMS as $room): ?>
                <a class="room-tile" href="<?= e(url('location')) ?>#<?= e(slugify($room['name'])) ?>">
                    <img src="<?= e(asset('img/' . $room['image'])) ?>" alt="<?= e($room['name']) ?>" loading="lazy">
                    <span class="room-tile-info">
                        <span class="room-tile-name"><?= e($room['name']) ?></span>
                        <span class="room-tile-cap"><?= icon('capacity') ?> bis <?= (int) $room['capacity'] ?> Personen</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- GESCHICHTE ---------------------------------------------------------------->
<section class="section">
    <div class="container split">
        <div class="split-media">
            <img src="<?= e(asset('img/aussen-2.jpg')) ?>" alt="Der Müser-Turm in Bochum-Langendreer bei Nacht" loading="lazy">
            <span class="split-stamp"><strong>1978</strong>Rock Palast eröffnet</span>
        </div>
        <div class="split-copy">
            <p class="eyebrow">Geschichte</p>
            <h2 class="section-title">Im historischen Müser-Turm</h2>
            <p>1928 entstand das rund 30 Meter hohe Sudhaus der Müser-Brauerei – heute das Herz von Matrix und Rockpalast. Am 1. November 1978 eröffnete hier der Rock Palast und wurde schnell zur überregionalen Adresse für Rock-Fans.</p>
            <p>Seit 1998 trägt das Haus den Namen Matrix, seit 2014 ist die ehemalige Brauerei eine Station der Route der Industriekultur.</p>
            <a class="btn btn-outline" href="<?= e(url('location')) ?>">Mehr zur Location <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>

<!-- ANFAHRT ------------------------------------------------------------------->
<section class="section section-tight">
    <div class="container">
        <div class="visit-band">
            <div class="visit-item"><?= icon('pin') ?><div><strong><?= e(config('venue.street')) ?></strong><span><?= e(config('venue.zip')) ?> <?= e(config('venue.city')) ?></span></div></div>
            <div class="visit-item"><?= icon('mail') ?><div><strong>Kontakt</strong><a href="mailto:<?= e(config('venue.email')) ?>"><?= e(config('venue.email')) ?></a></div></div>
            <a class="btn btn-accent" href="<?= e(config('venue.route_url')) ?>" target="_blank" rel="noopener"><?= icon('route') ?> Route planen</a>
        </div>
    </div>
</section>
