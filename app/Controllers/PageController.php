<?php
declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    /** Räume der Location (Inhalte von der bisherigen Website übernommen). */
    public const ROOMS = [
        [
            'name' => 'Tube', 'image' => 'tube.jpg', 'capacity' => 700, 'size' => '360 m²', 'bars' => 3,
            'stage' => '8,5 × 7,5 × 1 m', 'extra' => 'Backstage 170 m²',
            'text' => 'Die größte Konzert- und Eventhalle. Direkt angeschlossen: ein großer Backstagebereich mit vier Räumen, zwei Toiletten und zwei Duschen. Davor ein Foyer mit großer Theke und direktem Zugang zum Backstage.',
        ],
        [
            'name' => 'Rockpalast', 'image' => 'rockpalast.jpg', 'capacity' => 300, 'size' => '210 m²', 'bars' => 2,
            'stage' => '5,2 × 5,2 m (Eckbühne)', 'extra' => null,
            'text' => 'Hier hat 1978 alles angefangen. 2014 kernsaniert und um eine Eckbühne erweitert – heute die perfekte Location für Konzerte, Partys und Events. Mit dem benachbarten Café ergeben sich weitere Möglichkeiten.',
        ],
        [
            'name' => 'Halle 5', 'image' => 'halle5.jpg', 'capacity' => 320, 'size' => '220 m²', 'bars' => 0,
            'stage' => null, 'extra' => null,
            'text' => 'Mit Platz für bis zu 320 Personen geeignet für Vorträge und Lesungen sowie als Clubfloor im Partybetrieb.',
        ],
        [
            'name' => 'Bizarre Club', 'image' => 'bizarre-club.jpg', 'capacity' => 250, 'size' => '120 m²', 'bars' => 1,
            'stage' => '7 × 5,5 × 0,5 m', 'extra' => null,
            'text' => 'Kleine, über Stufen erreichbare Bühne – ideal für intensive Clubshows, Vorträge, Lesungen und als Clubfloor im Partybetrieb.',
        ],
        [
            'name' => 'Katakomben', 'image' => 'katakomben.jpg', 'capacity' => 250, 'size' => '220 m²', 'bars' => 1,
            'stage' => null, 'extra' => 'Cocktailbar',
            'text' => 'Beeindruckende Säulen und Deckenbögen: Raum für Lesungen, Akustikkonzerte und Clubbetrieb. Die große Theke mit Cocktailbar lädt zum Verweilen ein.',
        ],
        [
            'name' => "Hell's Kitchen", 'image' => 'hells-kitchen.jpg', 'capacity' => 80, 'size' => '130 m²', 'bars' => 1,
            'stage' => null, 'extra' => 'Café',
            'text' => 'Gemütliches Café mit bequemen Sitzecken. Historische Brauerei-Elemente treffen moderne Einrichtung – für Hochzeiten, Firmenfeiern oder als Ruheort in einer langen Partynacht.',
        ],
    ];

    public function location(): void
    {
        view('site/location', ['title' => 'Location', 'rooms' => self::ROOMS]);
    }

    public function directions(): void
    {
        view('site/directions', ['title' => 'Anfahrt']);
    }

    public function jobs(): void
    {
        view('site/jobs', ['title' => 'Jobs']);
    }

    public function imprint(): void
    {
        view('site/imprint', ['title' => 'Impressum']);
    }

    public function privacy(): void
    {
        view('site/privacy', ['title' => 'Datenschutz']);
    }
}
