<?php

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Löscht alle Beispiel-Veranstaltungen aus CreateDemoEventsAction samt Bildern.
 */
class RemoveDemoEventsAction
{
    /**
     * @return int Anzahl gelöschter Veranstaltungen
     */
    public function execute(): int
    {
        $demoEvents = Event::query()
            ->where('description', 'like', '%'.CreateDemoEventsAction::DEMO_MARK.'%')
            ->get();

        $demoEvents->each->delete();

        return $demoEvents->count();
    }
}
