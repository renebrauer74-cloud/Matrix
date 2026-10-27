<?php

namespace App\Enums\Events;

/**
 * Für welche Termine einer Serie eine Änderung oder ein Löschen gilt.
 */
enum SeriesScopeEnum: string
{
    case Single = 'this';
    case Following = 'following';
    case All = 'all';

    /**
     * Auswahl beim Speichern im Formular.
     */
    public function label(): string
    {
        return match ($this) {
            self::Single => 'Nur diesen Termin',
            self::Following => 'Diesen & alle folgenden',
            self::All => 'Alle Termine der Serie',
        };
    }

    /**
     * Beschriftung der Lösch-Buttons.
     */
    public function deleteLabel(): string
    {
        return match ($this) {
            self::Single => 'Nur diesen Termin',
            self::Following => 'Ab hier alle',
            self::All => 'Ganze Serie',
        };
    }
}
