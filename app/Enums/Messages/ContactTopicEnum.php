<?php

namespace App\Enums\Messages;

/**
 * Themen-Auswahl im Kontaktformular.
 */
enum ContactTopicEnum: string
{
    case General = 'general';
    case Events = 'events';
    case Booking = 'booking';
    case Press = 'press';
    case Feedback = 'feedback';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Allgemeine Frage',
            self::Events => 'Veranstaltungen & Tickets',
            self::Booking => 'Booking & Künstler',
            self::Press => 'Presse & Medien',
            self::Feedback => 'Lob & Kritik',
            self::Other => 'Sonstiges',
        };
    }
}
