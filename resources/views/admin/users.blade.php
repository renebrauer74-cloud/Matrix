<x-layouts.admin title="Benutzer">
    <div class="two-col">
        <section class="panel">
            <div class="panel-head"><h2>Admin-Benutzer</h2></div>
            <ul class="user-list">
@foreach ($users as $user)
                <li>
                    <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="user-info">
                        <strong>{{ $user->name }} @if ($user->is(auth()->user()))<span class="tag">Du</span>@endif</strong>
                        <span class="muted">{{ $user->email }}</span>
                        <small class="muted">Letzter Login: {{ $user->last_login_at?->format('d.m.Y H:i') ?? 'noch nie' }}</small>
                    </span>
@unless ($user->is(auth()->user()))
                    <form method="post" action="{{ route('admin.users.destroy', $user) }}" data-confirm="{{ $user->name }} wirklich löschen?">
                        @csrf
                        @method('DELETE')
                        <button class="icon-btn" type="submit" title="Löschen"><x-icon name="trash" /><span class="sr-only">Löschen</span></button>
                    </form>
@endunless
                </li>
@endforeach
            </ul>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Neuen Benutzer anlegen</h2></div>
            <form method="post" action="{{ route('admin.users.store') }}" autocomplete="off">
                @csrf
                <div @class(['field', 'has-error' => $errors->has('name')])>
                    <label for="u-name">Name</label>
                    <input id="u-name" name="name" required value="{{ old('name') }}">
                    <x-admin.field-error name="name" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('email')])>
                    <label for="u-email">E-Mail</label>
                    <input id="u-email" name="email" type="email" required value="{{ old('email') }}">
                    <x-admin.field-error name="email" />
                </div>
                <div @class(['field', 'has-error' => $errors->has('password')])>
                    <label for="u-pass">Passwort</label>
                    <input id="u-pass" name="password" type="password" minlength="10" required autocomplete="new-password">
                    <x-admin.field-error name="password" />
                    <p class="hint">Mindestens 10 Zeichen. Der Benutzer kann es danach unter „Mein Konto“ ändern.</p>
                </div>
                <button class="btn btn-accent" type="submit"><x-icon name="plus" /> Benutzer anlegen</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
