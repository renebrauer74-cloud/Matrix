<?php

namespace App\Actions\Events;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\ImageFocusEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\Location\RoomEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\User;

/**
 * Legt zehn Beispiel-Veranstaltungen mit Bildern aus public/assets/img/ an.
 * Erkennbar am Vermerk DEMO_MARK in der Beschreibung (siehe RemoveDemoEventsAction).
 */
class CreateDemoEventsAction
{
    public const DEMO_MARK = '[Demo]';

    public function __construct(private GenerateEventSlugAction $generateSlug) {}

    /**
     * @return int Anzahl angelegter Veranstaltungen
     */
    public function execute(): int
    {
        $author = User::query()->oldest('id')->first();
        $description = "Hier steht die Beschreibung der Veranstaltung. Absätze, **fett** und Links wie https://matrix-bochum.de werden automatisch formatiert.\n\nDies ist eine Beispiel-Veranstaltung. ".self::DEMO_MARK;

        foreach ($this->demoEvents() as [$title, $subtitle, $category, $label, $days, $time, $doors, $room, $image, $ticketStatus, $featured, $presale, $box, $age]) {
            $event = new Event([
                'title' => $title,
                'subtitle' => $subtitle,
                'category' => $category,
                'category_label' => $label,
                'description' => $description,
                'starts_at' => today()->addDays($days)->setTimeFromTimeString($time),
                'doors_at' => $doors,
                'room' => $room->value,
                'image_focus' => ImageFocusEnum::Center,
                'ticket_url' => $ticketStatus->allowsTicketSale() ? config('matrix.venue.tickets_url') : null,
                'price_presale' => $presale,
                'price_box' => $box,
                'age_rating' => $age,
                'ticket_status' => $ticketStatus,
                'status' => EventStatusEnum::Published,
                'is_featured' => $featured,
                'created_by' => $author?->getKey(),
            ]);
            $event->slug = $this->generateSlug->execute($event);
            $event->save();

            if ($image !== null) {
                $event->addMedia(public_path('assets/img/'.$image))
                    ->preservingOriginal()
                    ->toMediaCollection(MediaCollectionEnum::Events->value);
            }
        }

        return count($this->demoEvents());
    }

    /**
     * @return list<array{string, string, EventCategoryEnum, string, int, string, ?string, RoomEnum, ?string, TicketStatusEnum, bool, ?string, ?string, ?string}>
     */
    private function demoEvents(): array
    {
        return [
            ['Neon Nights', 'Die große Clubnacht auf drei Floors', EventCategoryEnum::Party, '2000s & Charts', 4, '23:00', null, RoomEnum::WholeVenue, 'tube.jpg', TicketStatusEnum::Available, true, '8 €', '10 €', 'ab 18'],
            ['Steel & Thunder', 'Support: Iron Pulse', EventCategoryEnum::Konzert, 'Heavy Metal', 7, '20:00', '19:00', RoomEnum::Tube, 'rockpalast.jpg', TicketStatusEnum::Few, true, '24 €', '29 €', null],
            ['Dark Romance', 'Gothic · Wave · EBM', EventCategoryEnum::Party, 'Gothic', 10, '22:00', null, RoomEnum::Katakomben, 'katakomben.jpg', TicketStatusEnum::Available, false, null, '7 €', 'ab 18'],
            ['Abend der Worte', 'Lesung mit anschließender Diskussion', EventCategoryEnum::Sonstiges, 'Lesung', 13, '19:30', '19:00', RoomEnum::HellsKitchen, 'hells-kitchen.jpg', TicketStatusEnum::Available, false, '12 €', '15 €', null],
            ['Grunge Revival', 'A Tribute to the 90s', EventCategoryEnum::Konzert, 'Tribute', 16, '20:00', '19:00', RoomEnum::Rockpalast, null, TicketStatusEnum::SoldOut, false, '18 €', null, null],
            ['Bizarre Beats', 'Techno bis zum Morgen', EventCategoryEnum::Party, 'Techno', 18, '23:00', null, RoomEnum::BizarreClub, 'bizarre-club.jpg', TicketStatusEnum::Available, false, null, '12 €', 'ab 18'],
            ['Punk im Pott', 'Drei Bands, ein Abend', EventCategoryEnum::Konzert, 'Punk', 24, '19:00', '18:00', RoomEnum::Tube, 'halle5.jpg', TicketStatusEnum::Available, false, '20 €', '25 €', null],
            ['Vinyl-Börse', 'Platten, CDs & Merch – Eintritt frei', EventCategoryEnum::Sonstiges, 'Flohmarkt', 27, '12:00', null, RoomEnum::Halle5, null, TicketStatusEnum::Free, false, null, null, null],
            ['Rock Palast Classics', '70er · 80er · Rock-Hymnen', EventCategoryEnum::Party, 'Rock Classics', 31, '22:00', null, RoomEnum::Rockpalast, 'aussen.jpg', TicketStatusEnum::Available, false, null, '6 €', 'ab 18'],
            ['Stand-up im Turm', 'Comedy-Mixed-Show', EventCategoryEnum::Sonstiges, 'Comedy', 36, '20:00', '19:00', RoomEnum::BizarreClub, null, TicketStatusEnum::Available, false, '16 €', '19 €', null],
        ];
    }
}
