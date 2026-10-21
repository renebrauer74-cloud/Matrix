<main class="auth-wrap">
    <form class="auth-card" method="post" action="<?= e(url('admin/login')) ?>">
        <?= csrf_field() ?>
        <img class="auth-logo" src="<?= e(asset('img/logo.png')) ?>" alt="Matrix Bochum" width="110">
        <h1>Admin-Bereich</h1>
        <p class="muted">Melde dich an, um Veranstaltungen zu verwalten.</p>

        <?php foreach (flash_messages() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= icon($f['type'] === 'error' ? 'alert' : 'check') ?><span><?= e($f['message']) ?></span></div>
        <?php endforeach; ?>

        <div class="field">
            <label for="email">E-Mail</label>
            <input id="email" name="email" type="email" autocomplete="username" required autofocus value="<?= e(old('email')) ?>">
        </div>
        <div class="field">
            <label for="password">Passwort</label>
            <div class="password-wrap">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button type="button" class="icon-btn" data-toggle-password aria-label="Passwort anzeigen"><?= icon('eye') ?></button>
            </div>
        </div>
        <button class="btn btn-accent btn-block" type="submit">Anmelden</button>
        <a class="auth-back" href="<?= e(url('/')) ?>"><?= icon('arrow-left') ?> Zur Website</a>
    </form>
</main>
<script>
document.querySelector('[data-toggle-password]')?.addEventListener('click', (e) => {
    const i = document.getElementById('password');
    i.type = i.type === 'password' ? 'text' : 'password';
});
</script>
