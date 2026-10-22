<?php

namespace App\Actions\Events;

use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legt eine Veranstaltung an oder aktualisiert sie – inklusive Slug, Veranstalter und Bild.
 */
class SaveEventAction
{
    public function __construct(private GenerateEventSlugAction $generateSlug) {}

    /**
     * @param  array<string, mixed>  $attributes  Spalten aus EventRequest::eventAttributes()
     * @param  array<string, string|null>|null  $organizer  Stammdaten des externen Veranstalters, null = Matrix veranstaltet
     * @param  int|null  $organizerId  Bestehender Veranstalter, dessen Stammdaten aktualisiert werden
     */
    public function execute(
        Event $event,
        array $attributes,
        ?array $organizer,
        ?int $organizerId,
        ?UploadedFile $image,
        bool $removeImage,
        ?User $author,
    ): Event {
        DB::transaction(function () use ($event, $attributes, $organizer, $organizerId, $author): void {
            $event->fill($attributes);
            $event->slug = $this->generateSlug->execute($event);
            $event->organizer()->associate($organizer === null ? null : $this->saveOrganizer($organizer, $organizerId));

            if (! $event->exists) {
                $event->created_by = $author?->getKey();
            }

            $event->save();
        });

        if ($image !== null) {
            $event->addMedia($image)->toMediaCollection(MediaCollectionEnum::Events->value);
        } elseif ($removeImage) {
            $event->clearMediaCollection(MediaCollectionEnum::Events->value);
        }

        return $event;
    }

    /**
     * @param  array<string, string|null>  $attributes
     */
    private function saveOrganizer(array $attributes, ?int $organizerId): Organizer
    {
        $organizer = $organizerId !== null ? Organizer::query()->findOrFail($organizerId) : new Organizer;
        $organizer->fill($attributes)->save();

        return $organizer;
    }
}
