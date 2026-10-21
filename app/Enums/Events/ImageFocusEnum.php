<?php

namespace App\Enums\Events;

/**
 * Welcher Bildausschnitt in den Kacheln sichtbar bleibt.
 */
enum ImageFocusEnum: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';

    public function label(): string
    {
        return match ($this) {
            self::Top => 'Oben',
            self::Center => 'Mitte',
            self::Bottom => 'Unten',
        };
    }

    /**
     * Wert für die CSS-Eigenschaft object-position.
     */
    public function objectPosition(): string
    {
        return match ($this) {
            self::Top => 'center top',
            self::Center => 'center',
            self::Bottom => 'center bottom',
        };
    }
}
