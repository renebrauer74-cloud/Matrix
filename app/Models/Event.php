<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;
use App\Core\ImageUpload;
use DateTimeImmutable;

/**
 * Veranstaltung (Konzert, Party oder Sonstiges).
 * Instanzen kapseln eine DB-Zeile und liefern Anzeige-Helfer;
 * die statischen Methoden sind die Datenbankabfragen.
 */
final class Event
{
    public const CATEGORIES = [
        'konzert'   => ['label' => 'Konzert',   'plural' => 'Konzerte',  'slug' => 'konzerte'],
        'party'     => ['label' => 'Party',     'plural' => 'Partys',    'slug' => 'partys'],
        'sonstiges' => ['label' => 'Sonstiges', 'plural' => 'Sonstiges', 'slug' => 'sonstiges'],
    ];

    public const TICKET_STATUS = [
        'available' => 'Tickets erhältlich',
        'few'       => 'Wenige Tickets',
        'boxoffice' => 'Nur Abendkasse',
        'free'      => 'Eintritt frei',
        'soldout'   => 'Ausverkauft',
        'postponed' => 'Verlegt',
        'cancelled' => 'Abgesagt',
    ];

    public const FOCUS = ['top' => 'Oben', 'center' => 'Mitte', 'bottom' => 'Unten'];

    /** Ein Event gilt als "kommend", bis es vorbei ist (ohne Endzeit: 6 h nach Beginn). */
    private const NOT_OVER = 'COALESCE(e.ends_at, DATE_ADD(e.starts_at, INTERVAL 6 HOUR)) >= :now';

    private array $a;

    public function __construct(array $row)
    {
        $this->a = $row;
    }

    public function __get(string $key): mixed
    {
        return $this->a[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->a[$key]);
    }

    public function toArray(): array
    {
        return $this->a;
    }

    // --- Anzeige-Helfer ------------------------------------------------------

    public function start(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->a['starts_at']);
    }

    public function end(): ?DateTimeImmutable
    {
        return $this->a['ends_at'] ? new DateTimeImmutable($this->a['ends_at']) : null;
    }

    public function url(): string
    {
        return url('event/' . $this->a['slug']);
    }

    public function imageUrl(string $size = 'md'): ?string
    {
        $img = $this->a['image'] ?? null;
        return ImageUpload::exists($img) ? upload_url("{$img}-{$size}.webp") : null;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->a['category']]['label'] ?? 'Event';
    }

    /** Text für das Badge: bei "Sonstiges" die freie Bezeichnung (z. B. "Lesung"). */
    public function badge(): string
    {
        if ($this->a['category'] === 'sonstiges' && !empty($this->a['category_label'])) {
            return $this->a['category_label'];
        }
        return $this->categoryLabel();
    }

    /** Genre / Motto bei Konzert & Party (optional). */
    public function genre(): ?string
    {
        return $this->a['category'] !== 'sonstiges' && !empty($this->a['category_label'])
            ? $this->a['category_label'] : null;
    }

    public function ticketStatusLabel(): string
    {
        return self::TICKET_STATUS[$this->a['ticket_status']] ?? '';
    }

    /** Hinweis-Banderole auf der Karte (z. B. "Ausverkauft"). */
    public function ribbon(): ?string
    {
        return in_array($this->a['ticket_status'], ['few', 'soldout', 'cancelled', 'postponed', 'free'], true)
            ? $this->ticketStatusLabel() : null;
    }

    public function canBuyTickets(): bool
    {
        return !empty($this->a['ticket_url'])
            && in_array($this->a['ticket_status'], ['available', 'few'], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this->a['ticket_status'], ['cancelled', 'postponed'], true);
    }

    public function isPast(): bool
    {
        $end = $this->end() ?? $this->start()->modify('+6 hours');
        return $end < new DateTimeImmutable();
    }

    public function isDraft(): bool
    {
        return $this->a['status'] === 'draft';
    }

    public function priceText(): ?string
    {
        $parts = [];
        if (!empty($this->a['price_presale'])) {
            $parts[] = 'VVK ' . $this->a['price_presale'];
        }
        if (!empty($this->a['price_box'])) {
            $parts[] = 'AK ' . $this->a['price_box'];
        }
        return $parts ? implode(' · ', $parts) : null;
    }

    public function doorsTime(): ?string
    {
        return $this->a['doors_at'] ? substr($this->a['doors_at'], 0, 5) : null;
    }

    public function excerpt(int $len = 160): string
    {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->a['description'])) ?? '');
        $t = str_replace('**', '', $t);
        return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
    }

    // --- Abfragen -------------------------------------------------------------

    /** @return self[] */
    private static function map(array $rows): array
    {
        return array_map(static fn (array $r) => new self($r), $rows);
    }

    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** @return self[] */
    public static function upcoming(?string $category = null, ?int $limit = null, ?string $room = null): array
    {
        $sql = 'SELECT e.* FROM events e WHERE e.status = \'published\' AND ' . self::NOT_OVER;
        $params = ['now' => self::now()];
        if ($category && isset(self::CATEGORIES[$category])) {
            $sql .= ' AND e.category = :cat';
            $params['cat'] = $category;
        }
        if ($room) {
            $sql .= ' AND e.room = :room';
            $params['room'] = $room;
        }
        $sql .= ' ORDER BY e.starts_at ASC';
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return self::map(DB::all($sql, $params));
    }

    /**
     * Folien für das Startseiten-Karussell: zuerst die Highlights,
     * danach mit den nächsten Terminen aufgefüllt. @return self[]
     */
    public static function heroSlides(int $limit = 6): array
    {
        $rows = DB::all(
            'SELECT e.* FROM events e WHERE e.status = \'published\' AND ' . self::NOT_OVER
            . ' ORDER BY e.is_featured DESC, e.starts_at ASC LIMIT ' . (int) $limit,
            ['now' => self::now()]
        );
        return self::map($rows);
    }

    /** Anzahl kommender Events je Kategorie. */
    public static function upcomingCounts(): array
    {
        $rows = DB::all(
            'SELECT e.category, COUNT(*) AS n FROM events e WHERE e.status = \'published\' AND ' . self::NOT_OVER . ' GROUP BY e.category',
            ['now' => self::now()]
        );
        $counts = array_fill_keys(array_keys(self::CATEGORIES), 0);
        foreach ($rows as $r) {
            $counts[$r['category']] = (int) $r['n'];
        }
        $counts['all'] = array_sum($counts);
        return $counts;
    }

    /** Räume, in denen kommende Events stattfinden (für Filter). */
    public static function upcomingRooms(): array
    {
        return DB::query(
            'SELECT DISTINCT e.room FROM events e WHERE e.status = \'published\' AND e.room IS NOT NULL AND e.room <> \'\' AND ' . self::NOT_OVER . ' ORDER BY e.room',
            ['now' => self::now()]
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function findBySlug(string $slug, bool $withDrafts = false): ?self
    {
        $row = DB::one(
            'SELECT * FROM events WHERE slug = ?' . ($withDrafts ? '' : " AND status = 'published'"),
            [$slug]
        );
        return $row ? new self($row) : null;
    }

    public static function find(int $id): ?self
    {
        $row = DB::one('SELECT * FROM events WHERE id = ?', [$id]);
        return $row ? new self($row) : null;
    }

    /** @return self[] */
    public static function related(self $event, int $limit = 3): array
    {
        $rows = DB::all(
            'SELECT e.* FROM events e WHERE e.status = \'published\' AND e.id <> :id AND ' . self::NOT_OVER
            . ' ORDER BY (e.category = :cat) DESC, e.starts_at ASC LIMIT ' . (int) $limit,
            ['id' => $event->id, 'cat' => $event->category, 'now' => self::now()]
        );
        return self::map($rows);
    }

    /**
     * Liste für den Admin-Bereich.
     * @param string $scope upcoming|past|drafts|all
     * @return self[]
     */
    public static function adminList(string $scope = 'upcoming', ?string $category = null, ?string $q = null): array
    {
        $where = [];
        $params = [];
        switch ($scope) {
            case 'past':
                $where[] = 'NOT (' . self::NOT_OVER . ')';
                $params['now'] = self::now();
                break;
            case 'drafts':
                $where[] = "e.status = 'draft'";
                break;
            case 'all':
                break;
            default:
                $where[] = self::NOT_OVER;
                $params['now'] = self::now();
        }
        if ($category && isset(self::CATEGORIES[$category])) {
            $where[] = 'e.category = :cat';
            $params['cat'] = $category;
        }
        if ($q !== null && trim($q) !== '') {
            $where[] = '(e.title LIKE :q1 OR e.subtitle LIKE :q2 OR e.category_label LIKE :q3 OR e.room LIKE :q4)';
            $like = '%' . addcslashes(trim($q), '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $sql = 'SELECT e.* FROM events e' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY e.starts_at ' . ($scope === 'past' ? 'DESC' : 'ASC');
        return self::map(DB::all($sql, $params));
    }

    public static function stats(): array
    {
        $now = self::now();
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');
        return [
            'upcoming' => (int) DB::value('SELECT COUNT(*) FROM events e WHERE e.status = \'published\' AND ' . self::NOT_OVER, ['now' => $now]),
            'month'    => (int) DB::value('SELECT COUNT(*) FROM events WHERE status = \'published\' AND starts_at BETWEEN ? AND ?', [$monthStart, $monthEnd]),
            'drafts'   => (int) DB::value("SELECT COUNT(*) FROM events WHERE status = 'draft'"),
            'soldout'  => (int) DB::value('SELECT COUNT(*) FROM events e WHERE e.ticket_status = \'soldout\' AND ' . self::NOT_OVER, ['now' => $now]),
            'byCategory' => self::upcomingCounts(),
        ];
    }

    // --- Schreiben -----------------------------------------------------------

    public static function create(array $data): int
    {
        $data['slug'] = self::uniqueSlug($data['title'], $data['starts_at']);
        return DB::insert('events', $data);
    }

    public static function update(int $id, array $data): void
    {
        $data['slug'] = self::uniqueSlug($data['title'], $data['starts_at'], $id);
        DB::update('events', $data, $id);
    }

    public static function delete(int $id): void
    {
        $event = self::find($id);
        if ($event) {
            ImageUpload::delete($event->image);
            DB::query('DELETE FROM events WHERE id = ?', [$id]);
        }
    }

    public static function setStatus(int $id, string $status): void
    {
        DB::update('events', ['status' => $status === 'published' ? 'published' : 'draft'], $id);
    }

    private static function uniqueSlug(string $title, string $startsAt, ?int $ignoreId = null): string
    {
        $base = mb_substr(slugify($title), 0, 180, 'UTF-8') . '-' . substr($startsAt, 0, 10);
        $slug = $base;
        $i = 2;
        while (DB::value('SELECT 1 FROM events WHERE slug = ? AND id <> ?', [$slug, $ignoreId ?? 0])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
