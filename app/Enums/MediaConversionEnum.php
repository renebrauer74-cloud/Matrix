<?php

namespace App\Enums;

enum MediaConversionEnum: string
{
    /** WebP mit max. 760 px Breite – Kacheln, Listen, Hintergründe */
    case Medium = 'md';

    /** WebP mit max. 1600 px Breite – Detailseite, Social-Media-Vorschau */
    case Large = 'lg';

    /**
     * Maximale Breite der Conversion in Pixeln.
     */
    public function width(): int
    {
        return match ($this) {
            self::Medium => 760,
            self::Large => 1600,
        };
    }
}
