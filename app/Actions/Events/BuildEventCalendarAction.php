<?php

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Kalendereintrag im iCalendar-Format (.ics) zum Herunterladen.
 */
class BuildEventCalendarAction
{
    /**
     * Ohne Endzeit wird im Kalender diese Dauer eingetragen.
     */
    private const DEFAULT_CALENDAR_HOURS = 4;

    public function __construct(private SummarizeEventDescriptionAction $summarize) {}

    public function execute(Event $event): string
    {
        $start = $event->starts_at->copy()->utc();
        $end = ($event->ends_at ?? $event->starts_at->copy()->addHours(self::DEFAULT_CALENDAR_HOURS))->copy()->utc();

        $location = ($event->room ? $event->room.', ' : '').config('matrix.venue.name').', '
            .config('matrix.venue.street').', '.config('matrix.venue.zip').' '.config('matrix.venue.city');

        $description = trim(($event->subtitle ? $event->subtitle."\n\n" : '').$this->summarize->execute($event, 400));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Matrix Bochum//Events//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->getKey().'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($event->title),
            'LOCATION:'.$this->escape($location),
            'DESCRIPTION:'.$this->escape($description),
            'URL:'.route('events.show', $event),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private function escape(?string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $text);
    }
}
