<x-layouts.admin title="Mein Konto">
    <form class="panel narrow-panel" method="post" action="{{ route('admin.account.update') }}">
        @csrf
        @method('PUT')
        <h2 class="panel-title">Profil</h2>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('name')])>
                <label for="name">Name</label>
                <input id="name" name="name" required autocomplete="name" value="{{ old('name', $user->name) }}">
                <x-admin.field-error name="name" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('email')])>
                <label for="email">E-Mail (Login)</label>
                <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email', $user->email) }}">
                <x-admin.field-error name="email" />
            </div>
        </div>
        <button class="btn btn-accent" type="submit"><x-icon name="check" /> Profil speichern</button>
    </form>

    <form class="panel narrow-panel account-password" id="passwort" method="post" action="{{ route('admin.account.password.update') }}">
        @csrf
        @method('PUT')
        <h2 class="panel-title">Passwort ändern</h2>
@if (session('password_changed'))
        <div class="alert alert-success" role="status">
            <x-icon name="check" />
            <span>Dein Passwort wurde geändert. Ab sofort meldest du dich mit dem neuen Passwort an.</span>
        </div>
@endif
        {{-- Für Passwort-Manager: zu welchem Login das Passwort gehört --}}
        <input type="email" value="{{ $user->email }}" autocomplete="username" readonly hidden>
        <div @class(['field', 'has-error' => $errors->has('current_password')])>
            <label for="current_password">Aktuelles Passwort</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
            <x-admin.field-error name="current_password" />
        </div>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('password')])>
                <label for="password">Neues Passwort</label>
                <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
                <x-admin.field-error name="password" />
                <p class="hint">Mindestens 10 Zeichen.</p>
            </div>
            <div class="field">
                <label for="password_confirmation">Neues Passwort wiederholen</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" autocomplete="new-password">
            </div>
        </div>
        <button class="btn btn-accent" type="submit"><x-icon name="check" /> Passwort ändern</button>
    </form>
</x-layouts.admin>
