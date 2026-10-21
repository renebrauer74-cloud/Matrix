<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Location
    |--------------------------------------------------------------------------
    |
    | Adresse, Kontakt und Ticketshops – erscheinen in Header, Footer,
    | Anfahrt, Event-Detailseiten und im Kalender-Download.
    |
    */

    'venue' => [
        'name' => 'Matrix Bochum',
        'tagline' => 'Club · Live · Events im Müser-Turm',
        'street' => 'Hauptstraße 200',
        'zip' => '44892',
        'city' => 'Bochum-Langendreer',
        'locality' => 'Bochum',
        'phone' => '0234 292102',
        'email' => 'info@matrix-bochum.de',
        'jobs_email' => 'bewerbung@matrix-bochum.de',
        'maps_url' => 'https://www.google.de/maps/place/Matrix+Bochum/@51.4713953,7.3245742,17z',
        'route_url' => 'https://www.google.com/maps/dir/?api=1&destination=51.4715624,7.3242255',
        'tickets_url' => 'https://matrix.ticket.io/qHtEs0PL/',
        'concert_tickets_url' => 'https://matrix-bochum.reservix.de/events',
        'instagram' => env('MATRIX_INSTAGRAM_URL'),
        'facebook' => env('MATRIX_FACEBOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'max_kilobytes' => 12 * 1024,

        // Videos für Firmen- und Jobs-Seite (MP4, WebM, MOV)
        'video_max_kilobytes' => 100 * 1024,

        // Anhänge aus den Website-Formularen (Lebenslauf, Foto einer Fundsache)
        'attachment_max_kilobytes' => 10 * 1024,

        // Anhänge sind nicht öffentlich – auf Laravel Cloud einen privaten Bucket als Disk eintragen
        'private_disk' => env('MEDIA_PRIVATE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Posteingang
    |--------------------------------------------------------------------------
    |
    | Nachrichten pro Seite im Admin-Posteingang.
    |
    */

    'inbox' => [
        'per_page' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Startseite
    |--------------------------------------------------------------------------
    */

    'home' => [
        'slides' => 6,
        'events' => 24,
        'visible_events' => 9,
    ],

];
