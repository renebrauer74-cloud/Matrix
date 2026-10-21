<?php

namespace App\Actions\Messages;

use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;

/**
 * Offene Nachrichten je Art – für die Reiter im Posteingang und das Dashboard.
 */
class CountOpenMessagesAction
{
    /**
     * @return array<string, int> Schlüssel „all“ sowie je MessageTypeEnum-Wert
     */
    public function execute(): array
    {
        $byType = Message::query()->open()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $counts = ['all' => (int) $byType->sum()];

        foreach (MessageTypeEnum::cases() as $type) {
            $counts[$type->value] = (int) ($byType[$type->value] ?? 0);
        }

        return $counts;
    }
}
