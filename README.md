# Matrix Bochum – Website

Laravel-Website (Laravel 13, PHP 8.3+) mit Admin-Bereich für Veranstaltungen.

## Lokal (Laravel Herd)

- URL: http://matrix-bochum.test (verlinkt mit `herd link matrix-bochum`)
- Admin: http://matrix-bochum.test/admin

```bash
composer run setup                      # Abhängigkeiten, .env, App-Key, Migrationen
php artisan matrix:admin admin@example.de   # Admin anlegen oder Passwort neu setzen
php artisan matrix:demo                 # Beispiel-Veranstaltungen anlegen
php artisan matrix:demo --remove        # Beispiel-Veranstaltungen löschen
php artisan test --compact              # Tests (Pest)
```

## Aufbau

```
app/
  Actions/Events/        Fachlogik: Speichern, Duplizieren, Slug, Kalender, Google-Daten …
  Enums/                 Art, Ticket-Status, Status, Bildausschnitt, Räume, Media-Collections
  Http/Controllers/      öffentliche Seiten, Admin/ (Dashboard, Events, Benutzer, Konto), Auth/
  Http/Requests/         Formular-Validierung
  Models/                Event, User
  helpers.php            deutsche Datumsformate, Beschreibungstext formatieren
config/matrix.php        Adresse, Kontakt, Ticket-Links, Upload-Limit
resources/views/         Blade-Views, components/ (Layouts, Event-Karte, Icons …)
public/assets/           CSS, JavaScript, Bilder, Schriften (ohne Build-Schritt)
public/uploads/          hochgeladene Bilder (Spatie Media Library, Disk „uploads“)
```

Projektregeln für Actions, Models, Enums und Uploads stehen in `.ai/guidelines/` (über Laravel Boost in `AGENTS.md`).

## Veranstaltungen

- Arten: **Konzert**, **Party**, **Sonstiges** – bei „Sonstiges“ ist das Feld
  „Was für eine Veranstaltung?“ Pflicht (z. B. Lesung, Comedy).
- **Genres:** Mehrfachauswahl per Dropdown, neue Genres direkt eintippen. Filter im Programm (`?genre=`).
  Genres ohne Veranstaltung verschwinden beim Löschen automatisch.
- **Wiederkehrende Termine:** wöchentlich, alle 2 Wochen, monatlich (gleicher Wochentag oder Datum)
  oder frei gewählte Daten. Jeder Termin ist eine eigene Veranstaltung; alle teilen sich eine `series_id`.
  Bearbeiten und Löschen für „nur diesen“, „diesen & folgende“ oder „alle Termine“.
- **Erneut ansetzen:** `/admin/events/neu?vorlage={id}` übernimmt alle Angaben samt Bild und Video.
- **Highlight-Zeitraum:** z. B. „2 Wochen vorher“ bis zur Veranstaltung – erst dann steht das Event
  ganz vorne im Startseiten-Karussell. Tempo des Karussells: `matrix.home.slide_seconds`.
- **Video:** YouTube-Link (wird erst nach Klick geladen) und/oder eigenes MP4/WebM/MOV.
- Titel in Großbuchstaben bekommen automatisch das große **ẞ** (`caps()`, `normalize_caps()`).
- Bilder werden als WebP in zwei Größen gespeichert (760 px und 1600 px Breite).
- Entwürfe sind nur für eingeloggte Admins sichtbar.
- Jedes Event hat eine Detailseite, Kalender-Download (.ics) und strukturierte Daten für Google.
- Abgelaufene Admin-Sitzung (`SESSION_LIFETIME`, Standard 480 Minuten): Hinweis und Login statt Fehlerseite.

## Livegang

1. `.env` anlegen (Vorlage `.env.example`), Datenbank-Zugang eintragen, `APP_ENV=production`, `APP_DEBUG=false`.
2. `php artisan migrate --force` und `php artisan matrix:admin <email>`.
3. Impressum und Datenschutzerklärung in `resources/views/pages/imprint.blade.php` / `privacy.blade.php` prüfen und vervollständigen.
