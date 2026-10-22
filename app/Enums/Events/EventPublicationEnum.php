<?php

namespace App\Enums\Events;

/**
 * Auswahl „Veröffentlichung“ im Veranstaltungsformular.
 *
 * Gespeichert wird daraus der Status (EventStatusEnum) und bei „Geplant“ zusätzlich events.publish_at.
 */
enum EventPublicationEnum: string
{
    case Published = 'published';
    case Scheduled = 'scheduled';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Online',
            self::Scheduled => 'Geplant',
            self::Draft => 'Entwurf',
        };
    }

    /**
     * Symbol aus public/assets/img/icons.svg.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Published => 'eye',
            self::Scheduled => 'clock',
            self::Draft => 'eye-off',
        };
    }
}
