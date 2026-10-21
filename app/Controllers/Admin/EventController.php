<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\ImageUpload;
use App\Core\Session;
use App\Models\Event;
use DateTimeImmutable;
use RuntimeException;

final class EventController
{
    public function index(): void
    {
        $scope = in_array($_GET['ansicht'] ?? '', ['upcoming', 'past', 'drafts', 'all'], true) ? $_GET['ansicht'] : 'upcoming';
        $category = isset(Event::CATEGORIES[$_GET['kategorie'] ?? '']) ? $_GET['kategorie'] : null;
        $q = trim((string) ($_GET['q'] ?? ''));

        view('admin/events/index', [
            'title'    => 'Veranstaltungen',
            'events'   => Event::adminList($scope, $category, $q),
            'scope'    => $scope,
            'category' => $category,
            'q'        => $q,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        view('admin/events/form', [
            'title' => 'Neue Veranstaltung',
            'event' => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate(null);
        if ($errors) {
            $this->failValidation($errors, 'admin/events/neu');
        }
        $data['created_by'] = Auth::id();
        $id = Event::create($data);
        flash('success', '„' . $data['title'] . '“ wurde ' . ($data['status'] === 'draft' ? 'als Entwurf gespeichert.' : 'veröffentlicht.'));
        redirect($this->afterSaveTarget($id));
    }

    public function edit(string $id): void
    {
        $event = $this->findOr404((int) $id);
        view('admin/events/form', [
            'title' => 'Bearbeiten: ' . $event->title,
            'event' => $event,
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        $event = $this->findOr404((int) $id);
        [$data, $errors] = $this->validate($event);
        if ($errors) {
            $this->failValidation($errors, 'admin/events/' . $event->id . '/bearbeiten');
        }
        if ($data['image'] !== $event->image) {
            ImageUpload::delete($event->image);
        }
        Event::update((int) $event->id, $data);
        flash('success', 'Änderungen an „' . $data['title'] . '“ gespeichert.');
        redirect($this->afterSaveTarget((int) $event->id));
    }

    public function destroy(string $id): void
    {
        $event = $this->findOr404((int) $id);
        Event::delete((int) $event->id);
        flash('success', '„' . $event->title . '“ wurde gelöscht.');
        redirect('admin/events');
    }

    public function duplicate(string $id): void
    {
        $event = $this->findOr404((int) $id);
        $data = $event->toArray();
        unset($data['id'], $data['slug'], $data['created_at'], $data['updated_at']);
        $data['title'] = $event->title . ' (Kopie)';
        $data['status'] = 'draft';
        $data['is_featured'] = 0;
        $data['image'] = ImageUpload::copy($event->image);
        $data['created_by'] = Auth::id();
        $newId = Event::create($data);
        flash('success', 'Kopie als Entwurf angelegt – Datum anpassen und veröffentlichen.');
        redirect('admin/events/' . $newId . '/bearbeiten');
    }

    public function toggleStatus(string $id): void
    {
        $event = $this->findOr404((int) $id);
        $new = $event->isDraft() ? 'published' : 'draft';
        Event::setStatus((int) $event->id, $new);
        flash('success', '„' . $event->title . '“ ist jetzt ' . ($new === 'published' ? 'veröffentlicht.' : 'ein Entwurf.'));
        back('admin/events');
    }

    // ------------------------------------------------------------------------

    private function afterSaveTarget(int $id): string
    {
        return ($_POST['after'] ?? '') === 'new' ? 'admin/events/neu' : 'admin/events/' . $id . '/bearbeiten';
    }

    private function findOr404(int $id): Event
    {
        $event = Event::find($id);
        if (!$event) {
            flash('error', 'Veranstaltung nicht gefunden.');
            redirect('admin/events');
        }
        return $event;
    }

    private function failValidation(array $errors, string $target): never
    {
        $old = $_POST;
        unset($old['_token']);
        Session::flash('_old', $old);
        Session::flash('_errors', $errors);
        flash('error', 'Bitte die markierten Felder prüfen.');
        redirect($target);
    }

    /** @return array{0: array, 1: array<string,string>} */
    private function validate(?Event $existing): array
    {
        $in = static fn (string $k) => trim((string) ($_POST[$k] ?? ''));
        $nullable = static fn (string $v) => $v === '' ? null : $v;
        $errors = [];

        // Bild zuerst verarbeiten, damit es bei Validierungsfehlern erhalten bleibt
        $image = $existing?->image;
        $pending = $in('image_pending');
        if ($pending !== '' && ImageUpload::exists($pending)) {
            $image = $pending;
        }
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $new = ImageUpload::fromRequest($_FILES['image']);
                if ($pending !== '' && $pending !== $existing?->image) {
                    ImageUpload::delete($pending);
                }
                $image = $new;
                $_POST['image_pending'] = $new;
            } catch (RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        } elseif (!empty($_POST['remove_image'])) {
            $image = null;
            $_POST['image_pending'] = '';
        }

        $title = $in('title');
        if ($title === '') {
            $errors['title'] = 'Bitte einen Titel eingeben.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'] = 'Maximal 200 Zeichen.';
        }

        $category = $in('category');
        if (!isset(Event::CATEGORIES[$category])) {
            $errors['category'] = 'Bitte eine Art auswählen.';
        }
        $label = $in('category_label');
        if ($category === 'sonstiges' && $label === '') {
            $errors['category_label'] = 'Bitte angeben, was für eine Veranstaltung es ist (z. B. Lesung).';
        } elseif (mb_strlen($label) > 80) {
            $errors['category_label'] = 'Maximal 80 Zeichen.';
        }

        $date = $in('date');
        $time = $in('time');
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time");
        if (!$start || $start->format('Y-m-d H:i') !== "$date $time") {
            $errors['date'] = 'Bitte gültiges Datum und Uhrzeit angeben.';
        }

        $doors = $in('doors');
        if ($doors !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $doors)) {
            $errors['doors'] = 'Ungültige Uhrzeit.';
        }

        $endsAt = null;
        $endTime = $in('end_time');
        if ($endTime !== '') {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $endTime)) {
                $errors['end_time'] = 'Ungültige Uhrzeit.';
            } elseif ($start) {
                $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $endTime");
                if ($end <= $start) {
                    $end = $end->modify('+1 day'); // endet nach Mitternacht
                }
                $endsAt = $end->format('Y-m-d H:i:s');
            }
        }

        $room = $in('room');
        if ($room === '__custom') {
            $room = $in('room_custom');
        }
        if (mb_strlen($room) > 80) {
            $errors['room'] = 'Maximal 80 Zeichen.';
        }

        $ticketUrl = $in('ticket_url');
        if ($ticketUrl !== '' && !preg_match('#^https?://#i', $ticketUrl)) {
            $ticketUrl = 'https://' . $ticketUrl;
        }
        if ($ticketUrl !== '' && !filter_var($ticketUrl, FILTER_VALIDATE_URL)) {
            $errors['ticket_url'] = 'Bitte einen gültigen Link angeben.';
        }

        $ticketStatus = $in('ticket_status');
        if (!isset(Event::TICKET_STATUS[$ticketStatus])) {
            $ticketStatus = 'available';
        }
        $focus = $in('image_focus');
        if (!isset(Event::FOCUS[$focus])) {
            $focus = 'center';
        }

        foreach (['subtitle' => 255, 'price_presale' => 60, 'price_box' => 60, 'age_rating' => 40] as $field => $max) {
            if (mb_strlen($in($field)) > $max) {
                $errors[$field] = "Maximal $max Zeichen.";
            }
        }

        $data = [
            'title'          => $title,
            'subtitle'       => $nullable($in('subtitle')),
            'category'       => $category,
            'category_label' => $nullable($label),
            'description'    => $nullable($in('description')),
            'starts_at'      => $start ? $start->format('Y-m-d H:i:s') : '',
            'doors_at'       => $nullable($doors),
            'ends_at'        => $endsAt,
            'room'           => $nullable($room),
            'image'          => $image,
            'image_focus'    => $focus,
            'ticket_url'     => $nullable($ticketUrl),
            'price_presale'  => $nullable($in('price_presale')),
            'price_box'      => $nullable($in('price_box')),
            'age_rating'     => $nullable($in('age_rating')),
            'ticket_status'  => $ticketStatus,
            'status'         => $in('status') === 'draft' ? 'draft' : 'published',
            'is_featured'    => empty($_POST['is_featured']) ? 0 : 1,
        ];

        return [$data, $errors];
    }
}
