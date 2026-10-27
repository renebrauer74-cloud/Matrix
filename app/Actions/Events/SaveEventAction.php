<?php

namespace App\Actions\Events;

use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legt eine Veranstaltung an oder aktualisiert sie – inklusive Slug, Veranstalter, Genres, Bild und Video.
 */
class SaveEventAction
{
    public function __construct(private GenerateEventSlugAction $generateSlug) {}

    /**
     * @param  array<string, mixed>  $attributes  Spalten aus EventRequest::eventAttributes()
     * @param  array<string, string|null>|null  $organizer  Stammdaten des externen Veranstalters, null = Matrix veranstaltet
     * @param  int|null  $organizerId  Bestehender Veranstalter, dessen Stammdaten aktualisiert werden
     * @param  list<int>|null  $genreIds  null = Genres unverändert lassen
     * @param  Event|null  $template  „Erneut ansetzen“: Bild und Video dieser Veranstaltung übernehmen, sofern nichts Neues hochgeladen wurde
     */
    public function execute(
        Event $event,
        array $attributes,
        ?array $organizer,
        ?int $organizerId,
        ?UploadedFile $image,
        bool $removeImage,
        ?User $author,
        ?array $genreIds = null,
        ?UploadedFile $video = null,
        bool $removeVideo = false,
        ?Event $template = null,
    ): Event {
        DB::transaction(function () use ($event, $attributes, $organizer, $organizerId, $author, $genreIds): void {
            $event->fill($attributes);
            $event->slug = $this->generateSlug->execute($event);
            $event->organizer()->associate($organizer === null ? null : $this->saveOrganizer($organizer, $organizerId));

            if (! $event->exists) {
                $event->created_by = $author?->getKey();
            }

            $event->save();

            if ($genreIds !== null) {
                $event->genres()->sync($genreIds);
            }
        });

        $this->saveMedia($event, MediaCollectionEnum::Events, $image, $removeImage, $template);
        $this->saveMedia($event, MediaCollectionEnum::EventVideo, $video, $removeVideo, $template);

        return $event;
    }

    private function saveMedia(Event $event, MediaCollectionEnum $collection, ?UploadedFile $file, bool $remove, ?Event $template): void
    {
        if ($file !== null) {
            $event->addMedia($file)->toMediaCollection($collection->value);
        } elseif ($remove) {
            $event->clearMediaCollection($collection->value);
        } elseif ($template !== null && ! $event->hasMedia($collection->value)) {
            $template->getFirstMedia($collection->value)?->copy($event, $collection->value);
        }
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
