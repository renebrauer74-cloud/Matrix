<?php

namespace App\Enums\Messages;

/**
 * Über welches Formular der Website eine Nachricht hereinkam.
 */
enum MessageTypeEnum: string
{
    case Contact = 'contact';
    case LostItem = 'lost-item';
    case Application = 'application';
    case CorporateInquiry = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Kontakt',
            self::LostItem => 'Fundsache',
            self::Application => 'Bewerbung',
            self::CorporateInquiry => 'Firmenanfrage',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Contact => 'Kontakt',
            self::LostItem => 'Fundsachen',
            self::Application => 'Bewerbungen',
            self::CorporateInquiry => 'Firmenanfragen',
        };
    }

    /**
     * Symbol aus public/assets/img/icons.svg.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Contact => 'mail',
            self::LostItem => 'search',
            self::Application => 'user',
            self::CorporateInquiry => 'capacity',
        };
    }
}
