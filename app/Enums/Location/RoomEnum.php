<?php

namespace App\Enums\Location;

use Illuminate\Support\Str;

/**
 * Räume im Müser-Turm – Auswahl im Admin-Formular und Inhalte der Location-Seite.
 *
 * Der Wert steht als Text in events.room; dort sind auch eigene Orte (z. B. „Biergarten“) erlaubt.
 */
enum RoomEnum: string
{
    case Tube = 'Tube';
    case Rockpalast = 'Rockpalast';
    case Halle5 = 'Halle 5';
    case BizarreClub = 'Bizarre Club';
    case Katakomben = 'Katakomben';
    case HellsKitchen = "Hell's Kitchen";
    case WholeVenue = 'Gesamte Matrix';

    /**
     * Die einzelnen Räume ohne „Gesamte Matrix“ – für Location-Seite und Startseite.
     *
     * @return list<self>
     */
    public static function spaces(): array
    {
        return array_values(array_filter(self::cases(), fn (self $room): bool => $room !== self::WholeVenue));
    }

    /**
     * Sprungmarke auf der Location-Seite, z. B. /location#bizarre-club.
     */
    public function anchor(): string
    {
        return Str::slug($this->value);
    }

    /**
     * Foto unter public/assets/img/.
     */
    public function image(): ?string
    {
        return match ($this) {
            self::Tube => 'tube.jpg',
            self::Rockpalast => 'rockpalast.jpg',
            self::Halle5 => 'halle5.jpg',
            self::BizarreClub => 'bizarre-club.jpg',
            self::Katakomben => 'katakomben.jpg',
            self::HellsKitchen => 'hells-kitchen.jpg',
            self::WholeVenue => null,
        };
    }

    public function capacity(): ?int
    {
        return match ($this) {
            self::Tube => 700,
            self::Rockpalast => 300,
            self::Halle5 => 320,
            self::BizarreClub, self::Katakomben => 250,
            self::HellsKitchen => 80,
            self::WholeVenue => null,
        };
    }

    public function size(): ?string
    {
        return match ($this) {
            self::Tube => '360 m²',
            self::Rockpalast => '210 m²',
            self::Halle5, self::Katakomben => '220 m²',
            self::BizarreClub => '120 m²',
            self::HellsKitchen => '130 m²',
            self::WholeVenue => null,
        };
    }

    /**
     * Anzahl der Theken.
     */
    public function bars(): int
    {
        return match ($this) {
            self::Tube => 3,
            self::Rockpalast => 2,
            self::Halle5, self::WholeVenue => 0,
            self::BizarreClub, self::Katakomben, self::HellsKitchen => 1,
        };
    }

    public function stage(): ?string
    {
        return match ($this) {
            self::Tube => '8,5 × 7,5 × 1 m',
            self::Rockpalast => '5,2 × 5,2 m (Eckbühne)',
            self::BizarreClub => '7 × 5,5 × 0,5 m',
            default => null,
        };
    }

    public function extra(): ?string
    {
        return match ($this) {
            self::Tube => 'Backstage 170 m²',
            self::Katakomben => 'Cocktailbar',
            self::HellsKitchen => 'Café',
            default => null,
        };
    }

    public function description(): ?string
    {
        return match ($this) {
            self::Tube => 'Die größte Konzert- und Eventhalle. Direkt angeschlossen: ein großer Backstagebereich mit vier Räumen, zwei Toiletten und zwei Duschen. Davor ein Foyer mit großer Theke und direktem Zugang zum Backstage.',
            self::Rockpalast => 'Hier hat 1978 alles angefangen. 2014 kernsaniert und um eine Eckbühne erweitert – heute die perfekte Location für Konzerte, Partys und Events. Mit dem benachbarten Café ergeben sich weitere Möglichkeiten.',
            self::Halle5 => 'Mit Platz für bis zu 320 Personen geeignet für Vorträge und Lesungen sowie als Clubfloor im Partybetrieb.',
            self::BizarreClub => 'Kleine, über Stufen erreichbare Bühne – ideal für intensive Clubshows, Vorträge, Lesungen und als Clubfloor im Partybetrieb.',
            self::Katakomben => 'Beeindruckende Säulen und Deckenbögen: Raum für Lesungen, Akustikkonzerte und Clubbetrieb. Die große Theke mit Cocktailbar lädt zum Verweilen ein.',
            self::HellsKitchen => 'Gemütliches Café mit bequemen Sitzecken. Historische Brauerei-Elemente treffen moderne Einrichtung – für Hochzeiten, Firmenfeiern oder als Ruheort in einer langen Partynacht.',
            self::WholeVenue => null,
        };
    }
}
