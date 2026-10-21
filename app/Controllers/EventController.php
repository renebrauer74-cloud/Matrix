<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;

final class EventController
{
    public function index(string $kategorie = ''): void
    {
        $category = null;
        foreach (Event::CATEGORIES as $key => $c) {
            if ($c['slug'] === $kategorie) {
                $category = $key;
            }
        }
        $rooms = Event::upcomingRooms();
        $room = isset($_GET['raum']) && in_array($_GET['raum'], $rooms, true) ? $_GET['raum'] : null;

        $events = Event::upcoming($category, null, $room);

        // Nach Monat gruppieren
        $months = [];
        foreach ($events as $event) {
            $key = $event->start()->format('Y-m');
            $months[$key] ??= ['label' => de_month($event->start()) . ' ' . $event->start()->format('Y'), 'events' => []];
            $months[$key]['events'][] = $event;
        }

        view('site/events', [
            'title'    => $category ? Event::CATEGORIES[$category]['plural'] : 'Programm',
            'category' => $category,
            'months'   => $months,
            'total'    => count($events),
            'counts'   => Event::upcomingCounts(),
            'rooms'    => $rooms,
            'room'     => $room,
        ]);
    }

    public function concerts(): void
    {
        redirect('programm/konzerte');
    }

    public function parties(): void
    {
        redirect('programm/partys');
    }

    public function show(string $slug): void
    {
        $event = $this->findOr404($slug);
        view('site/event', [
            'title'   => $event->title,
            'event'   => $event,
            'related' => Event::related($event, 3),
            'metaDescription' => $event->excerpt(155) ?: $event->subtitle,
            'ogImage' => $event->imageUrl('lg'),
        ]);
    }

    /** Kalendereintrag (.ics) zum Herunterladen. */
    public function ics(string $slug): void
    {
        $event = $this->findOr404($slug);
        $utc = new \DateTimeZone('UTC');
        $start = $event->start()->setTimezone($utc);
        $end = ($event->end() ?? $event->start()->modify('+4 hours'))->setTimezone($utc);
        $esc = static fn (?string $s) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $s);
        $host = $_SERVER['HTTP_HOST'] ?? 'matrix-bochum.de';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $location = trim(($event->room ? $event->room . ', ' : '') . config('venue.name') . ', '
            . config('venue.street') . ', ' . config('venue.zip') . ' ' . config('venue.city'));

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Matrix Bochum//Events//DE', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-' . $event->id . '@' . $host,
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART:' . $start->format('Ymd\THis\Z'),
            'DTEND:' . $end->format('Ymd\THis\Z'),
            'SUMMARY:' . $esc($event->title),
            'LOCATION:' . $esc($location),
            'DESCRIPTION:' . $esc(trim(($event->subtitle ? $event->subtitle . "\n\n" : '') . $event->excerpt(400))),
            'URL:' . $scheme . '://' . $host . $event->url(),
            'END:VEVENT', 'END:VCALENDAR',
        ];
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $event->slug . '.ics"');
        echo implode("\r\n", $lines) . "\r\n";
    }

    private function findOr404(string $slug): Event
    {
        $event = Event::findBySlug($slug, Auth::check());
        if (!$event) {
            http_response_code(404);
            view('errors/404', ['title' => 'Veranstaltung nicht gefunden']);
            exit;
        }
        return $event;
    }
}
