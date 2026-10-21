<?php
/**
 * @var array $stats
 * @var App\Models\Event[] $next
 */
use App\Core\Auth;
use App\Models\Event;

$hour = (int) date('G');
$greet = $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend');
?>
<section class="welcome">
    <div>
        <h2><?= e($greet) ?>, <?= e(explode(' ', (string) (Auth::user()['name'] ?? ''))[0]) ?>!</h2>
        <p class="muted">Hier ist der Überblick über das Programm.</p>
    </div>
</section>

<div class="stats">
    <a class="stat" href="<?= e(url('admin/events')) ?>">
        <span class="stat-label"><?= icon('calendar') ?> Kommende Events</span>
        <strong class="stat-value"><?= $stats['upcoming'] ?></strong>
    </a>
    <a class="stat" href="<?= e(url('admin/events?ansicht=all')) ?>">
        <span class="stat-label"><?= icon('clock') ?> Diesen Monat</span>
        <strong class="stat-value"><?= $stats['month'] ?></strong>
    </a>
    <a class="stat" href="<?= e(url('admin/events?ansicht=drafts')) ?>">
        <span class="stat-label"><?= icon('eye-off') ?> Entwürfe</span>
        <strong class="stat-value"><?= $stats['drafts'] ?></strong>
    </a>
    <a class="stat" href="<?= e(url('admin/events')) ?>">
        <span class="stat-label"><?= icon('ticket') ?> Ausverkauft</span>
        <strong class="stat-value"><?= $stats['soldout'] ?></strong>
    </a>
</div>

<div class="dash-grid">
    <section class="panel">
        <div class="panel-head">
            <h2>Als Nächstes</h2>
            <a class="link" href="<?= e(url('admin/events')) ?>">Alle anzeigen</a>
        </div>
        <?php if ($next): ?>
            <ul class="mini-list">
                <?php foreach ($next as $ev): $s = $ev->start(); ?>
                    <li class="cat-<?= e($ev->category) ?>">
                        <a href="<?= e(url('admin/events/' . $ev->id . '/bearbeiten')) ?>">
                            <span class="mini-date"><b><?= e($s->format('d')) ?></b><?= e(de_month($s, true)) ?></span>
                            <span class="mini-thumb"><?php if ($img = $ev->imageUrl('md')): ?><img src="<?= e($img) ?>" alt="" loading="lazy"><?php endif; ?></span>
                            <span class="mini-body">
                                <strong><?= e($ev->title) ?></strong>
                                <span><span class="dot"></span><?= e($ev->badge()) ?> · <?= e(de_weekday($s, true)) ?> <?= e($s->format('H:i')) ?><?= $ev->room ? ' · ' . e($ev->room) : '' ?></span>
                            </span>
                            <?php if ($ev->isDraft()): ?><span class="tag tag-draft">Entwurf</span><?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty">
                <p>Noch keine kommenden Veranstaltungen.</p>
                <a class="btn btn-accent" href="<?= e(url('admin/events/neu')) ?>"><?= icon('plus') ?> Erste Veranstaltung anlegen</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Nach Art</h2></div>
        <?php $total = max(1, $stats['byCategory']['all']); ?>
        <ul class="cat-bars">
            <?php foreach (Event::CATEGORIES as $key => $c): $n = $stats['byCategory'][$key]; ?>
                <li class="cat-<?= e($key) ?>">
                    <a href="<?= e(url('admin/events?kategorie=' . $key)) ?>">
                        <span class="cat-bars-label"><span class="dot"></span><?= e($c['plural']) ?></span>
                        <strong><?= $n ?></strong>
                    </a>
                    <span class="bar"><span style="width: <?= round($n / $total * 100) ?>%"></span></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="quick">
            <a class="quick-btn cat-konzert" href="<?= e(url('admin/events/neu?art=konzert')) ?>"><?= icon('guitar') ?> Konzert anlegen</a>
            <a class="quick-btn cat-party" href="<?= e(url('admin/events/neu?art=party')) ?>"><?= icon('party') ?> Party anlegen</a>
            <a class="quick-btn cat-sonstiges" href="<?= e(url('admin/events/neu?art=sonstiges')) ?>"><?= icon('sparkle') ?> Sonstiges anlegen</a>
        </div>
    </section>
</div>
