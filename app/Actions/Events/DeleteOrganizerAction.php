<?php

namespace App\Actions\Events;

use App\Models\Organizer;
use Illuminate\Support\Facades\DB;

/**
 * Löscht einen externen Veranstalter – seine Veranstaltungen bleiben erhalten und laufen danach als Matrix-Veranstaltung.
 */
class DeleteOrganizerAction
{
    /**
     * @return int Anzahl der Veranstaltungen, die nun ohne externen Veranstalter sind
     */
    public function execute(Organizer $organizer): int
    {
        return DB::transaction(function () use ($organizer): int {
            $detached = $organizer->events()->update(['organizer_id' => null]);
            $organizer->delete();

            return $detached;
        });
    }
}
