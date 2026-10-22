<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Location\LoadRoomsAction;
use App\Actions\Location\SaveRoomAction;
use App\Enums\Location\RoomEnum;
use App\Enums\Pages\PageEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function edit(string $room, LoadRoomsAction $loadRooms): View
    {
        return view('admin.rooms.form', [
            'room' => $this->findRoom($room, $loadRooms),
        ]);
    }

    public function update(RoomRequest $request, string $room, LoadRoomsAction $loadRooms, SaveRoomAction $saveRoom): RedirectResponse
    {
        $saved = $saveRoom->execute(
            $this->findRoom($room, $loadRooms),
            $request->roomAttributes(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return to_route('admin.pages.edit', PageEnum::Location)->withFragment('raeume')
            ->with('success', '„'.$saved->key->value.'“ gespeichert.');
    }

    /**
     * Raum aus dem URL-Teil (z. B. „bizarre-club“) – „Gesamte Matrix“ hat keine eigenen Angaben.
     */
    private function findRoom(string $anchor, LoadRoomsAction $loadRooms): Room
    {
        $key = RoomEnum::fromAnchor($anchor);

        return $loadRooms->execute()->first(fn (Room $room): bool => $room->key === $key) ?? abort(404);
    }
}
