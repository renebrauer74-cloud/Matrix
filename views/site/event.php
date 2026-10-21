<?php
/**
 * @var App\Models\Event $event
 * @var App\Models\Event[] $related
 */
use App\Core\Auth;

$s = $event->start();
$end = $event->end();
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$absUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'matrix-bochum.de') . $event->url();

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => $event->category === 'konzert' ? 'MusicEvent' : 'Event',
    'name' => $event->title,
    'startDate' => $s->format('c'),
    'eventStatus' => 'https://schema.org/' . match ($event->ticket_status) {
        'cancelled' => 'EventCancelled', 'postponed' => 'EventPostponed', default => 'EventScheduled',
    },
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => [
        '@type' => 'Place',
        'name' => config('venue.name') . ($event->room ? ' – ' . $event->room : ''),
        'address' => [
            '@type' => 'PostalAddress', 'streetAddress' => config('venue.street'),
            'postalCode' => config('venue.zip'), 'addressLocality' => 'Bochum', 'addressCountry' => 'DE',
        ],
    ],
    'url' => $absUrl,
];
if ($end) { $jsonLd['endDate'] = $end->format('c'); }
if ($event->imageUrl('lg')) { $jsonLd['image'] = [$scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $event->imageUrl('lg')]; }
if ($event->excerpt()) { $jsonLd['description'] = $event->excerpt(300); }
if ($event->ticket_url) { $jsonLd['offers'] = ['@type' => 'Offer', 'url' => $event->ticket_url, 'availability' => 'https://schema.org/' . ($event->ticket_status === 'soldout' ? 'SoldOut' : 'InStock')]; }
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<?php if ($event->isDraft() && Auth::check()): ?>
    <div class="draft-bar">
        <?= icon('eye-off') ?> Entwurf – nur für eingeloggte Admins sichtbar.
        <a href="<?= e(url('admin/events/' . $event->id . '/bearbeiten')) ?>">Bearbeiten</a>
    </div>
<?php endif; ?>

<article class="event-detail cat-<?= e($event->category) ?>">
    <div class="event-hero">
        <div class="hero-bg" aria-hidden="true">
            <?php if ($bg = $event->imageUrl('md')): ?><img src="<?= e($bg) ?>" alt=""><?php endif; ?>
        </div>
        <div class="container event-hero-inner">
            <a class="back-link" href="<?= e(url('programm')) ?>"><?= icon('arrow-left') ?> Zum Programm</a>

            <div class="event-hero-grid">
                <div class="event-poster">
                    <?= partial('partials/event-media', ['event' => $event, 'sizes' => '(min-width: 900px) 520px, 100vw', 'loading' => 'eager']) ?>
                    <?php if ($r = $event->ribbon()): ?><span class="ribbon ribbon-<?= e($event->ticket_status) ?>"><?= e($r) ?></span><?php endif; ?>
                </div>

                <div class="event-head">
                    <div class="event-card-tags">
                        <span class="cat-badge"><?= e($event->badge()) ?></span>
                        <?php if ($genre = $event->genre()): ?><span class="genre"><?= e($genre) ?></span><?php endif; ?>
                    </div>
                    <h1 class="event-title"><?= e($event->title) ?></h1>
                    <?php if ($event->subtitle): ?><p class="event-subtitle"><?= e($event->subtitle) ?></p><?php endif; ?>

                    <dl class="facts">
                        <div><dt><?= icon('calendar') ?> Datum</dt><dd><?= e(de_date_long($s)) ?></dd></div>
                        <div><dt><?= icon('clock') ?> Beginn</dt><dd><?= e($s->format('H:i')) ?> Uhr<?= $end ? ' – ' . e($end->format('H:i')) . ' Uhr' : '' ?></dd></div>
                        <?php if ($d = $event->doorsTime()): ?><div><dt><?= icon('door') ?> Einlass</dt><dd><?= e($d) ?> Uhr</dd></div><?php endif; ?>
                        <div><dt><?= icon('pin') ?> Ort</dt><dd><?= e($event->room ?: config('venue.name')) ?></dd></div>
                        <?php if ($p = $event->priceText()): ?><div><dt><?= icon('ticket') ?> Preis</dt><dd><?= e($p) ?></dd></div><?php endif; ?>
                        <?php if ($event->age_rating): ?><div><dt><?= icon('user') ?> Einlass</dt><dd><?= e($event->age_rating) ?></dd></div><?php endif; ?>
                    </dl>

                    <div class="event-actions">
                        <?php if ($event->canBuyTickets()): ?>
                            <a class="btn btn-accent btn-lg" href="<?= e($event->ticket_url) ?>" target="_blank" rel="noopener"><?= icon('ticket') ?> Tickets kaufen</a>
                        <?php else: ?>
                            <span class="status-pill status-<?= e($event->ticket_status) ?>"><?= e($event->ticketStatusLabel()) ?></span>
                        <?php endif; ?>
                        <a class="btn btn-outline" href="<?= e(url('event/' . $event->slug . '/kalender.ics')) ?>"><?= icon('calendar') ?> In Kalender</a>
                        <button class="btn btn-outline" type="button" data-share data-title="<?= e($event->title) ?>" data-url="<?= e($absUrl) ?>"><?= icon('share') ?> Teilen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container event-body">
        <div class="prose">
            <?php if ($event->description): ?>
                <?= format_text($event->description) ?>
            <?php else: ?>
                <p class="muted">Weitere Infos folgen.</p>
            <?php endif; ?>
        </div>
        <aside class="event-aside">
            <div class="aside-card">
                <h2>Location</h2>
                <p><strong><?= e(config('venue.name')) ?></strong><?= $event->room ? ' · ' . e($event->room) : '' ?><br>
                    <?= e(config('venue.street')) ?>, <?= e(config('venue.zip')) ?> <?= e(config('venue.city')) ?></p>
                <a class="link-arrow" href="<?= e(config('venue.route_url')) ?>" target="_blank" rel="noopener">Route planen <?= icon('arrow-right') ?></a>
            </div>
        </aside>
    </div>
</article>

<?php if ($related): ?>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2 class="section-title">Das könnte dir auch gefallen</h2>
                <a class="link-arrow hide-sm" href="<?= e(url('programm')) ?>">Gesamtes Programm <?= icon('arrow-right') ?></a>
            </div>
            <div class="events events-3">
                <?php foreach ($related as $r): ?>
                    <?= partial('partials/event-card', ['event' => $r]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
