<?php

namespace App\Enums\Messages;

/**
 * Reiter des Posteingangs im Admin-Bereich.
 */
enum MessageListFilterEnum: string
{
    case Open = 'open';
    case Done = 'done';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Done => 'Erledigt',
            self::All => 'Alle',
        };
    }
}
