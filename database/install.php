<?php
/**
 * Installation über die Kommandozeile:
 *
 *   php database/install.php                      Tabellen anlegen + ersten Admin erstellen
 *   php database/install.php --email=a@b.de --password=geheim --name="Max"
 *   php database/install.php --demo               zusätzlich Beispiel-Veranstaltungen anlegen
 *   php database/install.php --remove-demo        Beispiel-Veranstaltungen wieder löschen
 *
 * Wird kein Passwort übergeben, wird eins erzeugt und in storage/admin-zugang.txt abgelegt.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Nur über die Kommandozeile ausführbar.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database as DB;
use App\Core\ImageUpload;
use App\Models\Event;
use App\Models\User;

$opts = getopt('', ['email::', 'password::', 'name::', 'demo', 'remove-demo']);

// 1) Schema
$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(__DIR__ . '/schema.sql'));
foreach (array_filter(array_map('trim', preg_split('/;\s*$/m', (string) $sql) ?: [])) as $stmt) {
    DB::pdo()->exec($stmt);
}
echo "✓ Tabellen angelegt / aktuell\n";

foreach ([STORAGE_PATH . '/logs', UPLOAD_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// 2) Erster Admin
if (User::count() === 0) {
    $email = $opts['email'] ?? 'admin@matrix-bochum.de';
    $name = $opts['name'] ?? 'Administrator';
    $password = $opts['password'] ?? substr(strtr(base64_encode(random_bytes(18)), '+/', 'xy'), 0, 16);
    User::create($name, $email, $password);
    if (!isset($opts['password'])) {
        file_put_contents(
            STORAGE_PATH . '/admin-zugang.txt',
            "Matrix Bochum – Admin-Zugang\n/admin/login\n\nE-Mail:   $email\nPasswort: $password\n\n"
            . "Bitte nach dem ersten Login unter „Mein Konto“ ändern und diese Datei löschen.\n"
        );
        echo "✓ Admin angelegt – Zugangsdaten: storage/admin-zugang.txt\n";
    } else {
        echo "✓ Admin $email angelegt\n";
    }
} else {
    echo "• Admin-Benutzer existiert bereits\n";
}

// 3) Demo-Daten
const DEMO_MARK = '[Demo]';

if (isset($opts['remove-demo'])) {
    $ids = DB::query('SELECT id FROM events WHERE description LIKE ?', ['%' . DEMO_MARK . '%'])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        Event::delete((int) $id);
    }
    echo '✓ ' . count($ids) . " Demo-Veranstaltungen gelöscht\n";
}

if (isset($opts['demo'])) {
    $img = PUBLIC_PATH . '/assets/img/';
    $d = static fn (string $mod, string $time) => (new DateTimeImmutable($mod))->format('Y-m-d') . ' ' . $time . ':00';
    $demo = [
        ['Neon Nights', 'Die große Clubnacht auf drei Floors', 'party', '2000s & Charts', '+4 days', '23:00', null, 'Gesamte Matrix', 'tube.jpg', 'available', 1, '8 €', '10 €', 'ab 18'],
        ['Steel & Thunder', 'Support: Iron Pulse', 'konzert', 'Heavy Metal', '+7 days', '20:00', '19:00', 'Tube', 'rockpalast.jpg', 'few', 1, '24 €', '29 €', null],
        ['Dark Romance', 'Gothic · Wave · EBM', 'party', 'Gothic', '+10 days', '22:00', null, 'Katakomben', 'katakomben.jpg', 'available', 0, null, '7 €', 'ab 18'],
        ['Abend der Worte', 'Lesung mit anschließender Diskussion', 'sonstiges', 'Lesung', '+13 days', '19:30', '19:00', "Hell's Kitchen", 'hells-kitchen.jpg', 'available', 0, '12 €', '15 €', null],
        ['Grunge Revival', 'A Tribute to the 90s', 'konzert', 'Tribute', '+16 days', '20:00', '19:00', 'Rockpalast', null, 'soldout', 0, '18 €', null, null],
        ['Bizarre Beats', 'Techno bis zum Morgen', 'party', 'Techno', '+18 days', '23:00', null, 'Bizarre Club', 'bizarre-club.jpg', 'available', 0, null, '12 €', 'ab 18'],
        ['Punk im Pott', 'Drei Bands, ein Abend', 'konzert', 'Punk', '+24 days', '19:00', '18:00', 'Tube', 'halle5.jpg', 'available', 0, '20 €', '25 €', null],
        ['Vinyl-Börse', 'Platten, CDs & Merch – Eintritt frei', 'sonstiges', 'Flohmarkt', '+27 days', '12:00', null, 'Halle 5', null, 'free', 0, null, null, null],
        ['Rock Palast Classics', '70er · 80er · Rock-Hymnen', 'party', 'Rock Classics', '+31 days', '22:00', null, 'Rockpalast', 'aussen.jpg', 'available', 0, null, '6 €', 'ab 18'],
        ['Stand-up im Turm', 'Comedy-Mixed-Show', 'sonstiges', 'Comedy', '+36 days', '20:00', '19:00', 'Bizarre Club', null, 'available', 0, '16 €', '19 €', null],
    ];
    $text = "Hier steht die Beschreibung der Veranstaltung. Absätze, **fett** und Links wie https://matrix-bochum.de werden automatisch formatiert.\n\nDies ist eine Beispiel-Veranstaltung. " . DEMO_MARK;
    $adminId = (int) DB::value('SELECT id FROM users ORDER BY id LIMIT 1');
    foreach ($demo as [$title, $sub, $cat, $label, $mod, $time, $doors, $room, $file, $ts, $feat, $vvk, $ak, $age]) {
        Event::create([
            'title' => $title, 'subtitle' => $sub, 'category' => $cat, 'category_label' => $label,
            'description' => $text, 'starts_at' => $d($mod, $time), 'doors_at' => $doors, 'ends_at' => null,
            'room' => $room, 'image' => $file ? ImageUpload::fromPath($img . $file) : null, 'image_focus' => 'center',
            'ticket_url' => in_array($ts, ['available', 'few'], true) ? config('venue.tickets_url') : null,
            'price_presale' => $vvk, 'price_box' => $ak, 'age_rating' => $age, 'ticket_status' => $ts,
            'status' => 'published', 'is_featured' => $feat, 'created_by' => $adminId ?: null,
        ]);
    }
    echo '✓ ' . count($demo) . " Demo-Veranstaltungen angelegt (entfernen mit --remove-demo)\n";
}

echo "Fertig.\n";
