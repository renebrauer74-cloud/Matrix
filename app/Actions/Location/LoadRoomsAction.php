<?php

namespace App\Actions\Location;

use App\Enums\Location\RoomEnum;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Lädt die Räume in der Reihenfolge von RoomEnum::spaces() – noch nicht gespeicherte Räume
 * kommen als ungespeichertes Model mit den Ausgangswerten aus RoomEnum zurück.
 */
class LoadRoomsAction
{
    /**
     * @return Collection<int, Room>
     */
    public function execute(): Collection
    {
        $stored = Room::query()->with('media')->get()->keyBy(fn (Room $room): string => $room->key->value);

        return collect(RoomEnum::spaces())->map(
            fn (RoomEnum $key): Room => $stored->get($key->value) ?? new Room(['key' => $key, ...$key->defaults()]),
        );
    }
}
