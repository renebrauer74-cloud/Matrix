<?php

namespace App\Enums\Events;

/**
 * Art einer Veranstaltung – bestimmt Farbe, Filter und die Programm-Unterseite.
 */
enum EventCategoryEnum: string
{
    case Konzert = 'konzert';
    case Party = 'party';
    case Sonstiges = 'sonstiges';

    public function label(): string
    {
        return match ($this) {
            self::Konzert => 'Konzert',
            self::Party => 'Party',
            self::Sonstiges => 'Sonstiges',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Konzert => 'Konzerte',
            self::Party => 'Partys',
            self::Sonstiges => 'Sonstiges',
        };
    }

    /**
     * URL-Teil der Programm-Unterseite, z. B. /programm/konzerte.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Konzert => 'konzerte',
            self::Party => 'partys',
            self::Sonstiges => 'sonstiges',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Konzert => 'guitar',
            self::Party => 'party',
            self::Sonstiges => 'sparkle',
        };
    }

    /**
     * Kurzbeschreibung in der Auswahl im Admin-Formular.
     */
    public function hint(): string
    {
        return match ($this) {
            self::Konzert => 'Live-Musik, Bands, Tribute',
            self::Party => 'Clubnacht, DJs, Motto-Party',
            self::Sonstiges => 'Lesung, Comedy, Markt …',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $category) {
            if ($category->slug() === $slug) {
                return $category;
            }
        }

        return null;
    }
}
