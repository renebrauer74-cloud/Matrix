<?php

namespace App\Actions\Events;

use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Legt eine Veranstaltung an oder aktualisiert sie – inklusive Slug und Bild.
 */
class SaveEventAction
{
    public function __construct(private GenerateEventSlugAction $generateSlug) {}

    /**
     * @param  array<string, mixed>  $attributes  Spalten aus EventRequest::eventAttributes()
     */
    public function execute(Event $event, array $attributes, ?UploadedFile $image, bool $removeImage, ?User $author): Event
    {
        $event->fill($attributes);
        $event->slug = $this->generateSlug->execute($event);

        if (! $event->exists) {
            $event->created_by = $author?->getKey();
        }

        $event->save();

        if ($image !== null) {
            $event->addMedia($image)->toMediaCollection(MediaCollectionEnum::Events->value);
        } elseif ($removeImage) {
            $event->clearMediaCollection(MediaCollectionEnum::Events->value);
        }

        return $event;
    }
}
