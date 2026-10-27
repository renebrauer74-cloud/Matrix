<x-layouts.blank title="Login">
    <main class="auth-wrap">
        <form class="auth-card" method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <img class="auth-logo" src="{{ asset('assets/img/logo.png') }}" alt="Matrix Bochum" width="110">
            <h1>Admin-Bereich</h1>
            <p class="muted">Melde dich an, um Veranstaltungen zu verwalten.</p>

@if (session('success'))
            <div class="alert alert-success" role="alert"><x-icon name="check" /><span>{{ session('success') }}</span></div>
@endif
@if ($errors->any() || session('error'))
            <div class="alert alert-error" role="alert"><x-icon name="alert" /><span>{{ $errors->first() ?: session('error') }}</span></div>
@endif

            <div class="field">
                <label for="email">E-Mail</label>
                <input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}">
            </div>
            <div class="field">
                <label for="password">Passwort</label>
                <div class="password-wrap">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button type="button" class="icon-btn" data-toggle-password aria-label="Passwort anzeigen"><x-icon name="eye" /></button>
                </div>
            </div>
            <button class="btn btn-accent btn-block" type="submit">Anmelden</button>
            <a class="auth-back" href="{{ route('home') }}"><x-icon name="arrow-left" /> Zur Website</a>
        </form>
    </main>
    <script>
    document.querySelector('[data-toggle-password]')?.addEventListener('click', () => {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    });
    </script>
</x-layouts.blank>
