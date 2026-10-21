<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\Event;

/**
 * Strukturierte Daten (schema.org) für die Event-Detailseite – damit Google den Termin erkennt.
 */
class BuildEventStructuredDataAction
{
    public function __construct(private SummarizeEventDescriptionAction $summarize) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Event $event): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => $event->category === EventCategoryEnum::Konzert ? 'MusicEvent' : 'Event',
            'name' => $event->title,
            'startDate' => $event->starts_at->toIso8601String(),
            'eventStatus' => 'https://schema.org/'.match ($event->ticket_status) {
                TicketStatusEnum::Cancelled => 'EventCancelled',
                TicketStatusEnum::Postponed => 'EventPostponed',
                default => 'EventScheduled',
            },
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => [
                '@type' => 'Place',
                'name' => config('matrix.venue.name').($event->room ? ' – '.$event->room : ''),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => config('matrix.venue.street'),
                    'postalCode' => config('matrix.venue.zip'),
                    'addressLocality' => config('matrix.venue.locality'),
                    'addressCountry' => 'DE',
                ],
            ],
            'url' => route('events.show', $event),
        ];

        if ($event->ends_at) {
            $data['endDate'] = $event->ends_at->toIso8601String();
        }

        $image = $event->getFirstMediaUrl(MediaCollectionEnum::Events->value, MediaConversionEnum::Large->value);
        if ($image !== '') {
            $data['image'] = [$image];
        }

        $description = $this->summarize->execute($event, 300);
        if ($description !== '') {
            $data['description'] = $description;
        }

        if ($event->ticket_url) {
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => $event->ticket_url,
                'availability' => 'https://schema.org/'.($event->ticket_status === TicketStatusEnum::SoldOut ? 'SoldOut' : 'InStock'),
            ];
        }

        return $data;
    }
}
