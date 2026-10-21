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
  „Was für eine Veranstaltung?“ Pflicht (z. B. Lesung, Comedy). Bei Konzert/Party ist es
  optional als Genre/Motto nutzbar.
- Bilder werden als WebP in zwei Größen gespeichert (760 px und 1600 px Breite).
- „Highlight“ zeigt das Event groß im Slider der Startseite.
- Entwürfe sind nur für eingeloggte Admins sichtbar.
- Jedes Event hat eine Detailseite, Kalender-Download (.ics) und strukturierte Daten für Google.

## Livegang

1. `.env` anlegen (Vorlage `.env.example`), Datenbank-Zugang eintragen, `APP_ENV=production`, `APP_DEBUG=false`.
2. `php artisan migrate --force` und `php artisan matrix:admin <email>`.
3. Impressum und Datenschutzerklärung in `resources/views/pages/imprint.blade.php` / `privacy.blade.php` prüfen und vervollständigen.
