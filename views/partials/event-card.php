<?php
/** @var App\Models\Event $event */
$start = $event->start();
$hidden = !empty($hidden);
?>
<article class="event-card cat-<?= e($event->category) ?><?= $event->isCancelled() ? ' is-cancelled' : '' ?>" data-category="<?= e($event->category) ?>"<?= $hidden ? ' hidden' : '' ?>>
    <div class="event-card-media">
        <?= partial('partials/event-media', ['event' => $event]) ?>
        <time class="date-badge" datetime="<?= e($start->format('c')) ?>">
            <span class="date-wd"><?= e(de_weekday($start, true)) ?></span>
            <span class="date-day"><?= e($start->format('d')) ?></span>
            <span class="date-mon"><?= e(de_month($start, true)) ?></span>
        </time>
        <?php if ($ribbon = $event->ribbon()): ?>
            <span class="ribbon ribbon-<?= e($event->ticket_status) ?>"><?= e($ribbon) ?></span>
        <?php endif; ?>
    </div>

    <div class="event-card-body">
        <div class="event-card-tags">
            <span class="cat-badge"><?= e($event->badge()) ?></span>
            <?php if ($genre = $event->genre()): ?><span class="genre"><?= e($genre) ?></span><?php endif; ?>
        </div>
        <h3 class="event-card-title">
            <a class="stretched-link" href="<?= e($event->url()) ?>"><?= e($event->title) ?></a>
        </h3>
        <?php if ($event->subtitle): ?>
            <p class="event-card-sub"><?= e($event->subtitle) ?></p>
        <?php endif; ?>
        <ul class="event-meta">
            <li class="meta-date"><?= icon('calendar') ?> <?= e(de_date_short($start)) ?></li>
            <li><?= icon('clock') ?> <?= e($start->format('H:i')) ?> Uhr<?php if ($d = $event->doorsTime()): ?> <span class="muted">· Einlass <?= e($d) ?></span><?php endif; ?></li>
            <?php if ($event->room): ?><li><?= icon('pin') ?> <?= e($event->room) ?></li><?php endif; ?>
        </ul>
    </div>

    <div class="event-card-foot">
        <span class="price"><?= e($event->priceText() ?? $event->ticketStatusLabel()) ?></span>
        <?php if ($event->canBuyTickets()): ?>
            <a class="btn btn-sm btn-accent" href="<?= e($event->ticket_url) ?>" target="_blank" rel="noopener"><?= icon('ticket') ?> Tickets</a>
        <?php else: ?>
            <span class="btn btn-sm btn-ghost">Details <?= icon('arrow-right') ?></span>
        <?php endif; ?>
    </div>
</article>
