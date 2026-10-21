<?php
/**
 * Matrix Bochum – Konfiguration
 * Kopie als config/config.php anlegen und Werte anpassen.
 */
return [
    'app' => [
        'name'     => 'Matrix Bochum',
        'debug'    => false,           // lokal true, live false
        'timezone' => 'Europe/Berlin',
    ],

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'matrix_bochum',
        'user'     => 'root',
        'pass'     => '',
        'charset'  => 'utf8mb4',
    ],

    'venue' => [
        'name'        => 'Matrix Bochum',
        'tagline'     => 'Club · Live · Events im Müser-Turm',
        'street'      => 'Hauptstraße 200',
        'zip'         => '44892',
        'city'        => 'Bochum-Langendreer',
        'phone'       => '0234 292102',
        'email'       => 'info@matrix-bochum.de',
        'jobs_email'  => 'bewerbung@matrix-bochum.de',
        'maps_url'    => 'https://www.google.de/maps/place/Matrix+Bochum/@51.4713953,7.3245742,17z',
        'route_url'   => 'https://www.google.com/maps/dir/?api=1&destination=51.4715624,7.3242255',
        'tickets_url' => 'https://matrix.ticket.io/qHtEs0PL/',
        'concert_tickets_url' => 'https://matrix-bochum.reservix.de/events',
        'instagram'   => '',  // z. B. https://www.instagram.com/...
        'facebook'    => '',
    ],

    // Räume / Hallen – erscheinen im Admin als Auswahl und auf der Location-Seite
    'rooms' => [
        'Tube', 'Bizarre Club', 'Katakomben', 'Rockpalast', 'Halle 5', "Hell's Kitchen", 'Gesamte Matrix',
    ],

    'uploads' => [
        'max_mb' => 12,
    ],
];
