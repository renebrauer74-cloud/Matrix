<x-layouts.admin title="Mein Konto">
    <form class="panel narrow-panel" method="post" action="{{ route('admin.account.update') }}">
        @csrf
        @method('PUT')
        <h2 class="panel-title">Profil</h2>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('name')])>
                <label for="name">Name</label>
                <input id="name" name="name" required value="{{ old('name', $user->name) }}">
                <x-admin.field-error name="name" />
            </div>
            <div @class(['field', 'has-error' => $errors->has('email')])>
                <label for="email">E-Mail (Login)</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}">
                <x-admin.field-error name="email" />
            </div>
        </div>

        <h2 class="panel-title">Passwort ändern</h2>
        <p class="hint">Leer lassen, um das Passwort nicht zu ändern.</p>
        <div @class(['field', 'has-error' => $errors->has('current_password')])>
            <label for="current_password">Aktuelles Passwort</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password">
            <x-admin.field-error name="current_password" />
        </div>
        <div class="grid-2">
            <div @class(['field', 'has-error' => $errors->has('new_password')])>
                <label for="new_password">Neues Passwort</label>
                <input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password">
                <x-admin.field-error name="new_password" />
            </div>
            <div class="field">
                <label for="new_password_confirmation">Wiederholen</label>
                <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password">
            </div>
        </div>
        <button class="btn btn-accent" type="submit"><x-icon name="check" /> Speichern</button>
    </form>
</x-layouts.admin>
