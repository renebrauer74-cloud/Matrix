<?php
/** @var array $users */
use App\Core\Auth;

$err = static fn (string $f) => error_for($f) ? '<p class="field-error">' . e(error_for($f)) . '</p>' : '';
?>
<div class="two-col">
    <section class="panel">
        <div class="panel-head"><h2>Admin-Benutzer</h2></div>
        <ul class="user-list">
            <?php foreach ($users as $u): ?>
                <li>
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
                    <span class="user-info">
                        <strong><?= e($u['name']) ?><?= (int) $u['id'] === Auth::id() ? ' <span class="tag">Du</span>' : '' ?></strong>
                        <span class="muted"><?= e($u['email']) ?></span>
                        <small class="muted">Letzter Login: <?= $u['last_login_at'] ? e(date('d.m.Y H:i', strtotime($u['last_login_at']))) : 'noch nie' ?></small>
                    </span>
                    <?php if ((int) $u['id'] !== Auth::id()): ?>
                        <form method="post" action="<?= e(url('admin/benutzer/' . $u['id'] . '/loeschen')) ?>" data-confirm="<?= e($u['name']) ?> wirklich löschen?">
                            <?= csrf_field() ?>
                            <button class="icon-btn" type="submit" title="Löschen"><?= icon('trash') ?><span class="sr-only">Löschen</span></button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Neuen Benutzer anlegen</h2></div>
        <form method="post" action="<?= e(url('admin/benutzer')) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <div class="field<?= error_for('name') ? ' has-error' : '' ?>">
                <label for="u-name">Name</label>
                <input id="u-name" name="name" required value="<?= e(old('name')) ?>">
                <?= $err('name') ?>
            </div>
            <div class="field<?= error_for('email') ? ' has-error' : '' ?>">
                <label for="u-email">E-Mail</label>
                <input id="u-email" name="email" type="email" required value="<?= e(old('email')) ?>">
                <?= $err('email') ?>
            </div>
            <div class="field<?= error_for('password') ? ' has-error' : '' ?>">
                <label for="u-pass">Passwort</label>
                <input id="u-pass" name="password" type="password" minlength="10" required autocomplete="new-password">
                <?= $err('password') ?>
                <p class="hint">Mindestens 10 Zeichen. Der Benutzer kann es danach unter „Mein Konto“ ändern.</p>
            </div>
            <button class="btn btn-accent" type="submit"><?= icon('plus') ?> Benutzer anlegen</button>
        </form>
    </section>
</div>
