<?php

namespace App\Models;

use App\Enums\Location\RoomEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Gepflegte Angaben zu einem Raum (Tube, Rockpalast …) – der Name kommt aus RoomEnum.
 */
#[Fillable(['key', 'description', 'size', 'capacity', 'bars', 'stage', 'extra'])]
class Room extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => RoomEnum::class,
            'capacity' => 'integer',
            'bars' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::RoomImage->value)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (MediaConversionEnum::cases() as $conversion) {
            $this->addMediaConversion($conversion->value)
                ->fit(Fit::Max, $conversion->width(), $conversion->width() * 4)
                ->format('webp')
                ->quality(82)
                ->nonQueued();
        }
    }
}
