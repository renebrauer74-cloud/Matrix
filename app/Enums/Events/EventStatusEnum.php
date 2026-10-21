<?php

namespace App\Enums\Events;

/**
 * Entwürfe sind nur für eingeloggte Admins sichtbar.
 */
enum EventStatusEnum: string
{
    case Published = 'published';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Online',
            self::Draft => 'Entwurf',
        };
    }
}
