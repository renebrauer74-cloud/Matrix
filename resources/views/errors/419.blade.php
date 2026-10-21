<x-layouts.blank title="Sitzung abgelaufen">
    <main class="auth-wrap">
        <div class="auth-card">
            <h1>Sitzung abgelaufen</h1>
            <p>Das Formular war zu lange geöffnet. Bitte lade die Seite neu und versuche es noch einmal.</p>
            <a class="btn btn-accent" href="{{ url()->previous() }}">Zurück</a>
        </div>
    </main>
</x-layouts.blank>
