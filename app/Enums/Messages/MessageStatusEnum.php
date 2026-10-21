<?php

namespace App\Enums\Messages;

/**
 * Bearbeitungsstand einer Nachricht im Posteingang.
 */
enum MessageStatusEnum: string
{
    case New = 'new';
    case InProgress = 'in-progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::InProgress => 'In Bearbeitung',
            self::Done => 'Erledigt',
        };
    }

    /**
     * Symbol aus public/assets/img/icons.svg.
     */
    public function icon(): string
    {
        return match ($this) {
            self::New => 'sparkle',
            self::InProgress => 'clock',
            self::Done => 'check',
        };
    }
}
