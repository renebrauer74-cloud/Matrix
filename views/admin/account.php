<?php
/** @var array $user */
use App\Core\Session;

$hasOld = is_array(Session::flashed('_old'));
$err = static fn (string $f) => error_for($f) ? '<p class="field-error">' . e(error_for($f)) . '</p>' : '';
$cls = static fn (string $f) => error_for($f) ? ' has-error' : '';
?>
<form class="panel narrow-panel" method="post" action="<?= e(url('admin/konto')) ?>">
    <?= csrf_field() ?>
    <h2 class="panel-title">Profil</h2>
    <div class="grid-2">
        <div class="field<?= $cls('name') ?>">
            <label for="name">Name</label>
            <input id="name" name="name" required value="<?= e($hasOld ? old('name') : $user['name']) ?>">
            <?= $err('name') ?>
        </div>
        <div class="field<?= $cls('email') ?>">
            <label for="email">E-Mail (Login)</label>
            <input id="email" name="email" type="email" required value="<?= e($hasOld ? old('email') : $user['email']) ?>">
            <?= $err('email') ?>
        </div>
    </div>

    <h2 class="panel-title">Passwort ändern</h2>
    <p class="hint">Leer lassen, um das Passwort nicht zu ändern.</p>
    <div class="field<?= $cls('current_password') ?>">
        <label for="current_password">Aktuelles Passwort</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password">
        <?= $err('current_password') ?>
    </div>
    <div class="grid-2">
        <div class="field<?= $cls('new_password') ?>">
            <label for="new_password">Neues Passwort</label>
            <input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password">
            <?= $err('new_password') ?>
        </div>
        <div class="field<?= $cls('new_password_confirmation') ?>">
            <label for="new_password_confirmation">Wiederholen</label>
            <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password">
            <?= $err('new_password_confirmation') ?>
        </div>
    </div>
    <button class="btn btn-accent" type="submit"><?= icon('check') ?> Speichern</button>
</form>
