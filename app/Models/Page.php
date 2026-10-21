<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Enums\Pages\PageEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Im Admin-Bereich gepflegte Seite (Jobs, Firmen) – Texte als JSON, Bilder und Videos als Media.
 */
#[Fillable(['key', 'content'])]
class Page extends Model implements HasMedia
{
    use InteractsWithMedia;

    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    private const VIDEO_TYPES = ['video/mp4', 'application/mp4', 'video/webm', 'video/quicktime'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => PageEnum::class,
            'content' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::PageHero->value)
            ->singleFile()
            ->acceptsMimeTypes(self::IMAGE_TYPES);

        $this->addMediaCollection(MediaCollectionEnum::PageHeroVideo->value)
            ->singleFile()
            ->acceptsMimeTypes(self::VIDEO_TYPES);

        $this->addMediaCollection(MediaCollectionEnum::PageGallery->value)
            ->acceptsMimeTypes(self::IMAGE_TYPES);

        $this->addMediaCollection(MediaCollectionEnum::PageVideos->value)
            ->acceptsMimeTypes(self::VIDEO_TYPES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (MediaConversionEnum::cases() as $conversion) {
            $this->addMediaConversion($conversion->value)
                ->fit(Fit::Max, $conversion->width(), $conversion->width() * 4)
                ->format('webp')
                ->quality(82)
                ->performOnCollections(MediaCollectionEnum::PageHero->value, MediaCollectionEnum::PageGallery->value)
                ->nonQueued();
        }
    }
}
