<?php

namespace App\Actions\Messages;

use App\Models\Message;

/**
 * Ungelesene Nachrichten – Zähler neben „Nachrichten“ in der Admin-Navigation.
 */
class CountUnreadMessagesAction
{
    public function execute(): int
    {
        return Message::query()->unread()->count();
    }
}
