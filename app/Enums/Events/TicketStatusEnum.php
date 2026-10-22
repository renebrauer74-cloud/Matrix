<?php

namespace App\Enums\Events;

enum TicketStatusEnum: string
{
    case Available = 'available';
    case Few = 'few';
    case AlmostSoldOut = 'almostsoldout';
    case BoxOffice = 'boxoffice';
    case Free = 'free';
    case SoldOut = 'soldout';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tickets erhältlich',
            self::Few => 'Wenige Tickets',
            self::AlmostSoldOut => 'Fast ausverkauft',
            self::BoxOffice => 'Nur Abendkasse',
            self::Free => 'Eintritt frei',
            self::SoldOut => 'Ausverkauft',
            self::Postponed => 'Verlegt',
            self::Cancelled => 'Abgesagt',
        };
    }

    /**
     * Hinweis-Banderole auf Karte und Plakat (z. B. „Ausverkauft“).
     */
    public function showsRibbon(): bool
    {
        return in_array($this, [self::Few, self::AlmostSoldOut, self::SoldOut, self::Cancelled, self::Postponed, self::Free], true);
    }

    /**
     * Ticket-Link wird nur angeboten, solange es Tickets gibt.
     */
    public function allowsTicketSale(): bool
    {
        return in_array($this, [self::Available, self::Few, self::AlmostSoldOut], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this, [self::Cancelled, self::Postponed], true);
    }
}
