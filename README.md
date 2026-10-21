# Matrix Bochum – Website

Modulare PHP-Website (ohne Framework, PHP 8.2+) mit Admin-Bereich für Veranstaltungen.

## Lokal (Laravel Herd)

- URL: http://matrix-bochum.test (verlinkt mit `herd link matrix-bochum`)
- Admin: http://matrix-bochum.test/admin – Zugangsdaten stehen in `storage/admin-zugang.txt`
  (nach dem ersten Login unter „Mein Konto“ ändern und die Datei löschen)

```bash
php database/install.php                # Tabellen + ersten Admin anlegen
php database/install.php --demo         # Beispiel-Veranstaltungen anlegen
php database/install.php --remove-demo  # Beispiel-Veranstaltungen löschen
```

## Aufbau

```
app/
  bootstrap.php          Autoloader, Konfiguration, Fehlerbehandlung
  routes.php             alle URLs an einer Stelle
  helpers.php            e(), url(), asset(), Datumsformate, …
  Core/                  Router, Database (PDO), View, Session, Csrf, Auth, ImageUpload
  Models/                Event, User
  Controllers/           öffentliche Seiten
  Controllers/Admin/     Login, Dashboard, Veranstaltungen, Benutzer
views/
  layouts/               site, admin, blank
  partials/              Header, Footer, Event-Karte, Event-Bild …
  site/                  Startseite, Programm, Event-Detail, Location, Anfahrt, …
  admin/                 Dashboard, Event-Liste, Event-Formular, Benutzer, Konto
config/config.php        Datenbank, Adresse, Ticket-Links, Räume (nicht ins Git)
database/                schema.sql + install.php
public/                  Web-Root: index.php, assets/, uploads/
storage/                 Logs, Zugangsdatei
```

## Veranstaltungen

- Arten: **Konzert**, **Party**, **Sonstiges** – bei „Sonstiges“ ist das Eingabefeld
  „Was für eine Veranstaltung?“ Pflicht (z. B. Lesung, Comedy). Bei Konzert/Party ist es
  optional als Genre/Motto nutzbar.
- Bilder werden beim Upload geprüft, neu kodiert und als WebP in zwei Größen gespeichert.
- „Highlight“ zeigt das Event groß im Slider der Startseite.
- Entwürfe sind nur für eingeloggte Admins sichtbar.
- Jedes Event hat eine Detailseite, Kalender-Download (.ics) und strukturierte Daten für Google.

## Livegang

1. Dateien hochladen, Web-Root auf `public/` setzen (alternativ leitet `.htaccess` im Hauptordner um).
2. `config/config.example.php` → `config/config.php` kopieren, Zugangsdaten eintragen, `debug` auf `false`.
3. `php database/install.php` ausführen (oder `database/schema.sql` importieren).
4. Impressum und Datenschutzerklärung in `views/site/imprint.php` / `privacy.php` prüfen und vervollständigen.
