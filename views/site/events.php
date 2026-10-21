<?php
/**
 * @var ?string $category
 * @var array $months
 * @var int $total
 * @var array $counts
 * @var string[] $rooms
 * @var ?string $room
 */
use App\Models\Event;

$roomQuery = $room ? '?raum=' . rawurlencode($room) : '';
$catPath = $category ? 'programm/' . Event::CATEGORIES[$category]['slug'] : 'programm';
?>
<section class="page-hero">
    <div class="container">
        <p class="eyebrow">Programm</p>
        <h1 class="page-title"><?= e($category ? Event::CATEGORIES[$category]['plural'] : 'Alle Veranstaltungen') ?></h1>
        <p class="page-lead">
            <?= $total ?> <?= $total === 1 ? 'Termin' : 'Termine' ?><?= $room ? ' im Raum ' . e($room) : '' ?> – Konzerte, Partys und mehr im Müser-Turm.
        </p>
    </div>
</section>

<section class="section section-flush">
    <div class="container">
        <div class="toolbar toolbar-sticky">
            <nav class="chips" aria-label="Nach Art filtern">
                <a class="chip<?= !$category ? ' is-active' : '' ?>" href="<?= e(url('programm') . $roomQuery) ?>"<?= !$category ? ' aria-current="page"' : '' ?>>Alle <span><?= $counts['all'] ?></span></a>
                <?php foreach (Event::CATEGORIES as $key => $c): ?>
                    <a class="chip chip-<?= e($key) ?><?= $category === $key ? ' is-active' : '' ?>" href="<?= e(url('programm/' . $c['slug']) . $roomQuery) ?>"<?= $category === $key ? ' aria-current="page"' : '' ?>><?= e($c['plural']) ?> <span><?= $counts[$key] ?></span></a>
                <?php endforeach; ?>
            </nav>
            <div class="toolbar-right">
                <?php if ($rooms): ?>
                    <form class="room-filter" method="get" action="<?= e(url($catPath)) ?>" data-autosubmit>
                        <label class="sr-only" for="raum">Raum</label>
                        <select id="raum" name="raum">
                            <option value="">Alle Räume</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= e($r) ?>"<?= $r === $room ? ' selected' : '' ?>><?= e($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button class="btn btn-sm" type="submit">Filtern</button></noscript>
                    </form>
                <?php endif; ?>
                <?= partial('partials/view-toggle') ?>
            </div>
        </div>

        <?php if (!$months): ?>
            <div class="empty-state">
                <?= icon('calendar', 'icon icon-xl') ?>
                <h3>Aktuell keine Termine</h3>
                <p>Hier stehen gerade keine Veranstaltungen an. Schau bald wieder vorbei!</p>
                <?php if ($category || $room): ?><a class="btn btn-outline" href="<?= e(url('programm')) ?>">Alle Veranstaltungen zeigen</a><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($months as $key => $month): ?>
            <section class="month" aria-labelledby="m-<?= e($key) ?>">
                <h2 class="month-title" id="m-<?= e($key) ?>">
                    <?= e($month['label']) ?> <span><?= count($month['events']) ?></span>
                </h2>
                <div class="events" data-view-target>
                    <?php foreach ($month['events'] as $event): ?>
                        <?= partial('partials/event-card', ['event' => $event]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</section>
