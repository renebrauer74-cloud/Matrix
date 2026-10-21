<?php
/**
 * @var App\Models\Event[] $events
 * @var string $scope
 * @var ?string $category
 * @var string $q
 */
use App\Models\Event;

$scopes = ['upcoming' => 'Kommende', 'past' => 'Vergangene', 'drafts' => 'Entwürfe', 'all' => 'Alle'];
$qs = static fn (array $over) => '?' . http_build_query(array_filter(array_merge(
    ['ansicht' => $scope, 'kategorie' => $category, 'q' => $q], $over
), static fn ($v) => $v !== null && $v !== ''));
?>
<div class="list-toolbar">
    <nav class="tabs" aria-label="Zeitraum">
        <?php foreach ($scopes as $key => $label): ?>
            <a href="<?= e(url('admin/events') . $qs(['ansicht' => $key])) ?>"<?= $scope === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form class="list-filter" method="get" action="<?= e(url('admin/events')) ?>">
        <input type="hidden" name="ansicht" value="<?= e($scope) ?>">
        <label class="search">
            <?= icon('search') ?>
            <span class="sr-only">Suchen</span>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Titel, Raum, Genre …">
        </label>
        <select name="kategorie" aria-label="Art" onchange="this.form.submit()">
            <option value="">Alle Arten</option>
            <?php foreach (Event::CATEGORIES as $key => $c): ?>
                <option value="<?= e($key) ?>"<?= $category === $key ? ' selected' : '' ?>><?= e($c['plural']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$events): ?>
    <div class="panel empty">
        <?= icon('calendar', 'icon icon-xl') ?>
        <p><?= $q !== '' || $category ? 'Keine Veranstaltungen für diesen Filter gefunden.' : 'Hier gibt es noch keine Veranstaltungen.' ?></p>
        <a class="btn btn-accent" href="<?= e(url('admin/events/neu')) ?>"><?= icon('plus') ?> Neue Veranstaltung</a>
    </div>
<?php else: ?>
    <p class="list-count"><?= count($events) ?> <?= count($events) === 1 ? 'Veranstaltung' : 'Veranstaltungen' ?></p>
    <div class="event-table" role="list">
        <?php foreach ($events as $ev): $s = $ev->start(); ?>
            <article class="erow cat-<?= e($ev->category) ?><?= $ev->isPast() ? ' is-past' : '' ?>" role="listitem">
                <a class="erow-thumb" href="<?= e(url('admin/events/' . $ev->id . '/bearbeiten')) ?>" tabindex="-1" aria-hidden="true">
                    <?php if ($img = $ev->imageUrl('md')): ?>
                        <img src="<?= e($img) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="erow-noimg"><?= icon('image') ?></span>
                    <?php endif; ?>
                </a>
                <div class="erow-date">
                    <b><?= e($s->format('d')) ?></b>
                    <span><?= e(de_month($s, true)) ?> <?= e($s->format('y')) ?></span>
                    <small><?= e(de_weekday($s, true)) ?> <?= e($s->format('H:i')) ?></small>
                </div>
                <div class="erow-main">
                    <a class="erow-title" href="<?= e(url('admin/events/' . $ev->id . '/bearbeiten')) ?>"><?= e($ev->title) ?></a>
                    <div class="erow-meta">
                        <span class="erow-date-inline"><?= e(de_date_short($s)) ?> · <?= e($s->format('H:i')) ?></span>
                        <span class="pill pill-cat"><span class="dot"></span><?= e($ev->badge()) ?></span>
                        <?php if ($ev->genre()): ?><span class="muted"><?= e($ev->genre()) ?></span><?php endif; ?>
                        <?php if ($ev->room): ?><span class="muted"><?= icon('pin') ?> <?= e($ev->room) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="erow-status">
                    <?php if ($ev->isDraft()): ?><span class="tag tag-draft">Entwurf</span><?php else: ?><span class="tag tag-live">Online</span><?php endif; ?>
                    <?php if ($ev->is_featured): ?><span class="tag tag-star" title="Highlight auf der Startseite"><?= icon('star') ?></span><?php endif; ?>
                    <?php if ($ev->ribbon()): ?><span class="tag tag-<?= e($ev->ticket_status) ?>"><?= e($ev->ticketStatusLabel()) ?></span><?php endif; ?>
                </div>
                <div class="erow-actions">
                    <a class="icon-btn" href="<?= e(url('admin/events/' . $ev->id . '/bearbeiten')) ?>" title="Bearbeiten"><?= icon('edit') ?><span class="sr-only">Bearbeiten</span></a>
                    <a class="icon-btn" href="<?= e($ev->url()) ?>" target="_blank" rel="noopener" title="Ansehen"><?= icon('eye') ?><span class="sr-only">Ansehen</span></a>
                    <details class="menu">
                        <summary class="icon-btn" title="Mehr"><span class="dots" aria-hidden="true">•••</span><span class="sr-only">Mehr Aktionen</span></summary>
                        <div class="menu-pop">
                            <form method="post" action="<?= e(url('admin/events/' . $ev->id . '/status')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit"><?= icon($ev->isDraft() ? 'eye' : 'eye-off') ?> <?= $ev->isDraft() ? 'Veröffentlichen' : 'Offline nehmen' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('admin/events/' . $ev->id . '/duplizieren')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit"><?= icon('copy') ?> Duplizieren</button>
                            </form>
                            <form method="post" action="<?= e(url('admin/events/' . $ev->id . '/loeschen')) ?>" data-confirm="„<?= e($ev->title) ?>“ wirklich löschen? Das kann nicht rückgängig gemacht werden.">
                                <?= csrf_field() ?>
                                <button type="submit" class="danger"><?= icon('trash') ?> Löschen</button>
                            </form>
                        </div>
                    </details>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
