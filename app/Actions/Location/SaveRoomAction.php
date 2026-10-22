<?php

namespace App\Actions\Location;

use App\Enums\MediaCollectionEnum;
use App\Models\Room;
use Illuminate\Http\UploadedFile;

/**
 * Speichert die Angaben und das Foto eines Raums.
 */
class SaveRoomAction
{
    /**
     * @param  array<string, mixed>  $attributes  Felder aus RoomRequest::roomAttributes()
     */
    public function execute(Room $room, array $attributes, ?UploadedFile $image, bool $removeImage): Room
    {
        $room->fill($attributes)->save();

        if ($image !== null) {
            $room->addMedia($image)->toMediaCollection(MediaCollectionEnum::RoomImage->value);
        } elseif ($removeImage) {
            $room->clearMediaCollection(MediaCollectionEnum::RoomImage->value);
        }

        return $room;
    }
}
