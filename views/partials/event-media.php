<?php
/**
 * Bild eines Events – oder ein gestaltetes Ersatz-Cover, falls kein Bild hinterlegt ist.
 * @var App\Models\Event $event
 * @var string $sizes
 */
$md = $event->imageUrl('md');
$lg = $event->imageUrl('lg');
$focus = ['top' => 'center top', 'bottom' => 'center bottom'][$event->image_focus] ?? 'center';
?>
<?php if ($md): ?>
    <img src="<?= e($md) ?>" srcset="<?= e($md) ?> 760w, <?= e($lg) ?> 1600w" sizes="<?= e($sizes ?? '(min-width: 1100px) 360px, (min-width: 700px) 45vw, 100vw') ?>"
         alt="<?= e($event->title) ?>" loading="<?= e($loading ?? 'lazy') ?>" decoding="async" style="object-position: <?= e($focus) ?>">
<?php else: ?>
    <div class="cover-fallback" aria-hidden="true">
        <img class="cover-logo" src="<?= e(asset('img/logo.png')) ?>" alt="" loading="lazy">
        <span class="cover-kicker"><?= e($event->badge()) ?></span>
        <span class="cover-title"><?= e($event->title) ?></span>
    </div>
<?php endif; ?>
