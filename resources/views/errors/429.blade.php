<x-layouts.blank title="Zu viele Anfragen">
    <main class="auth-wrap">
        <div class="auth-card">
            <h1>Kurz durchatmen</h1>
            <p>Es wurden in kurzer Zeit zu viele Formulare abgeschickt. Bitte warte einen Moment und versuche es dann noch einmal.</p>
            <a class="btn btn-accent" href="{{ url()->previous() }}">Zurück</a>
        </div>
    </main>
</x-layouts.blank>
