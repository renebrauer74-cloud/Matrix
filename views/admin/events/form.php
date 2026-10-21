<?php
/** @var ?App\Models\Event $event */
use App\Core\Session;
use App\Models\Event;

$isEdit = $event !== null;
$hasOld = is_array(Session::flashed('_old'));
$start = $isEdit ? $event->start() : null;
$end = $isEdit ? $event->end() : null;

/** Wert: alte Eingabe > gespeicherter Wert > Standard */
$v = static function (string $key, mixed $stored = '', mixed $default = '') use ($hasOld): string {
    if ($hasOld) {
        return (string) old($key, '');
    }
    return (string) ($stored ?? $default ?? '');
};

$category = $v('category', $event?->category, isset(Event::CATEGORIES[$_GET['art'] ?? '']) ? $_GET['art'] : 'konzert');
$rooms = (array) config('rooms', []);
$storedRoom = (string) ($event?->room ?? '');
$roomValue = $hasOld ? (string) old('room', '') : ($storedRoom === '' ? '' : (in_array($storedRoom, $rooms, true) ? $storedRoom : '__custom'));
$roomCustom = $hasOld ? (string) old('room_custom', '') : ($roomValue === '__custom' ? $storedRoom : '');
$pending = $hasOld ? (string) old('image_pending', '') : '';
$imageName = $pending ?: ($event?->image ?? '');
$imageUrl = $imageName && \App\Core\ImageUpload::exists($imageName) ? upload_url($imageName . '-md.webp') : null;
$status = $v('status', $event?->status, 'published');
$featured = $hasOld ? !empty(old('is_featured')) : (bool) ($event?->is_featured ?? false);
$focus = $v('image_focus', $event?->image_focus, 'center');

$err = static fn (string $f) => error_for($f) ? '<p class="field-error">' . e(error_for($f)) . '</p>' : '';
$cls = static fn (string $f) => error_for($f) ? ' has-error' : '';

$catIcons = ['konzert' => 'guitar', 'party' => 'party', 'sonstiges' => 'sparkle'];
$catHints = ['konzert' => 'Live-Musik, Bands, Tribute', 'party' => 'Clubnacht, DJs, Motto-Party', 'sonstiges' => 'Lesung, Comedy, Markt …'];
?>
<form class="event-form" method="post" enctype="multipart/form-data"
      action="<?= e(url($isEdit ? 'admin/events/' . $event->id : 'admin/events')) ?>" data-event-form novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="image_pending" value="<?= e($pending) ?>">

    <div class="form-main">
        <!-- Art -->
        <section class="panel form-section">
            <h2 class="form-section-title"><span>1</span> Art der Veranstaltung</h2>
            <div class="cat-picker<?= $cls('category') ?>" role="radiogroup" aria-label="Art der Veranstaltung">
                <?php foreach (Event::CATEGORIES as $key => $c): ?>
                    <label class="cat-option cat-<?= e($key) ?>">
                        <input type="radio" name="category" value="<?= e($key) ?>"<?= $category === $key ? ' checked' : '' ?> data-cat-input>
                        <span class="cat-option-box">
                            <?= icon($catIcons[$key], 'icon cat-option-icon') ?>
                            <strong><?= e($c['label']) ?></strong>
                            <small><?= e($catHints[$key]) ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?= $err('category') ?>

            <div class="field<?= $cls('category_label') ?>" data-label-field>
                <label for="category_label" data-label-text
                       data-text-sonstiges="Was für eine Veranstaltung? *"
                       data-text-default="Genre / Motto (optional)">Genre / Motto (optional)</label>
                <input id="category_label" name="category_label" type="text" maxlength="80"
                       value="<?= e($v('category_label', $event?->category_label)) ?>"
                       list="label-suggestions"
                       data-ph-sonstiges="z. B. Lesung, Comedy, Flohmarkt, Vortrag"
                       data-ph-konzert="z. B. Metal, Punk, Tribute"
                       data-ph-party="z. B. 90er, Techno, Gothic">
                <datalist id="label-suggestions">
                    <option value="Lesung"><option value="Comedy"><option value="Vortrag"><option value="Flohmarkt">
                    <option value="Messe"><option value="Theater"><option value="Workshop"><option value="Private Feier">
                </datalist>
                <?= $err('category_label') ?>
            </div>
        </section>

        <!-- Basis -->
        <section class="panel form-section">
            <h2 class="form-section-title"><span>2</span> Titel &amp; Beschreibung</h2>
            <div class="field<?= $cls('title') ?>">
                <label for="title">Titel *</label>
                <input id="title" name="title" type="text" maxlength="200" required value="<?= e($v('title', $event?->title)) ?>" placeholder="z. B. Dirty Honey – Live in Bochum" data-preview="title">
                <?= $err('title') ?>
            </div>
            <div class="field<?= $cls('subtitle') ?>">
                <label for="subtitle">Untertitel / Support</label>
                <input id="subtitle" name="subtitle" type="text" maxlength="255" value="<?= e($v('subtitle', $event?->subtitle)) ?>" placeholder="z. B. Support: The Band" data-preview="subtitle">
                <?= $err('subtitle') ?>
            </div>
            <div class="field">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="7" placeholder="Infos zur Veranstaltung, Line-up, Hinweise …"><?= e($v('description', $event?->description)) ?></textarea>
                <p class="hint">Leerzeile = neuer Absatz · <code>**fett**</code> · Links werden automatisch klickbar.</p>
            </div>
        </section>

        <!-- Termin & Ort -->
        <section class="panel form-section">
            <h2 class="form-section-title"><span>3</span> Termin &amp; Ort</h2>
            <div class="grid-4">
                <div class="field<?= $cls('date') ?>">
                    <label for="date">Datum *</label>
                    <input id="date" name="date" type="date" required value="<?= e($v('date', $start?->format('Y-m-d'))) ?>" data-preview="date">
                </div>
                <div class="field<?= $cls('date') ?>">
                    <label for="time">Beginn *</label>
                    <input id="time" name="time" type="time" required value="<?= e($v('time', $start?->format('H:i'), '20:00')) ?>" data-preview="time">
                </div>
                <div class="field<?= $cls('doors') ?>">
                    <label for="doors">Einlass</label>
                    <input id="doors" name="doors" type="time" value="<?= e($v('doors', $event?->doorsTime())) ?>">
                </div>
                <div class="field<?= $cls('end_time') ?>">
                    <label for="end_time">Ende</label>
                    <input id="end_time" name="end_time" type="time" value="<?= e($v('end_time', $end?->format('H:i'))) ?>">
                </div>
            </div>
            <?= $err('date') ?><?= $err('doors') ?><?= $err('end_time') ?>
            <p class="hint">Endet die Veranstaltung nach Mitternacht, einfach die Uhrzeit eintragen – der Folgetag wird automatisch erkannt.</p>

            <div class="grid-2">
                <div class="field<?= $cls('room') ?>">
                    <label for="room">Raum</label>
                    <select id="room" name="room" data-room-select data-preview="room">
                        <option value="">– kein Raum angegeben –</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= e($r) ?>"<?= $roomValue === $r ? ' selected' : '' ?>><?= e($r) ?></option>
                        <?php endforeach; ?>
                        <option value="__custom"<?= $roomValue === '__custom' ? ' selected' : '' ?>>Anderer Ort …</option>
                    </select>
                    <?= $err('room') ?>
                </div>
                <div class="field" data-room-custom<?= $roomValue === '__custom' ? '' : ' hidden' ?>>
                    <label for="room_custom">Anderer Ort</label>
                    <input id="room_custom" name="room_custom" type="text" maxlength="80" value="<?= e($roomCustom) ?>" placeholder="z. B. Biergarten" data-preview="room">
                </div>
            </div>
        </section>

        <!-- Bild -->
        <section class="panel form-section">
            <h2 class="form-section-title"><span>4</span> Bild / Flyer</h2>
            <div class="dropzone<?= $imageUrl ? ' has-image' : '' ?><?= $cls('image') ?>" data-dropzone>
                <img class="dropzone-preview" src="<?= e($imageUrl ?? '') ?>" alt="" data-dropzone-img<?= $imageUrl ? '' : ' hidden' ?>>
                <div class="dropzone-empty">
                    <?= icon('upload', 'icon icon-xl') ?>
                    <strong>Bild hierher ziehen oder <u>auswählen</u></strong>
                    <span>JPG, PNG, WebP · max. <?= (int) config('uploads.max_mb', 12) ?> MB · wird automatisch optimiert</span>
                </div>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" data-dropzone-input aria-label="Bild auswählen">
            </div>
            <?= $err('image') ?>
            <div class="image-options" data-image-options<?= $imageUrl ? '' : ' hidden' ?>>
                <div class="field field-inline">
                    <span class="label">Bildausschnitt in Kacheln</span>
                    <div class="segmented">
                        <?php foreach (Event::FOCUS as $key => $label): ?>
                            <label><input type="radio" name="image_focus" value="<?= e($key) ?>"<?= $focus === $key ? ' checked' : '' ?> data-preview="focus"><span><?= e($label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($imageUrl): ?>
                    <label class="check danger-check"><input type="checkbox" name="remove_image" value="1" data-remove-image> Bild entfernen</label>
                <?php endif; ?>
            </div>
        </section>

        <!-- Tickets -->
        <section class="panel form-section">
            <h2 class="form-section-title"><span>5</span> Tickets &amp; Einlass</h2>
            <div class="field<?= $cls('ticket_url') ?>">
                <label for="ticket_url">Ticket-Link</label>
                <input id="ticket_url" name="ticket_url" type="url" inputmode="url" maxlength="500" value="<?= e($v('ticket_url', $event?->ticket_url)) ?>" placeholder="https://…">
                <?= $err('ticket_url') ?>
                <p class="hint">
                    Schnell einfügen:
                    <button type="button" class="link-btn" data-fill="ticket_url" data-value="<?= e(config('venue.tickets_url')) ?>">ticket.io (Partys)</button> ·
                    <button type="button" class="link-btn" data-fill="ticket_url" data-value="<?= e(config('venue.concert_tickets_url')) ?>">Reservix (Konzerte)</button>
                </p>
            </div>
            <div class="grid-3">
                <div class="field<?= $cls('price_presale') ?>">
                    <label for="price_presale">Vorverkauf</label>
                    <input id="price_presale" name="price_presale" type="text" maxlength="60" value="<?= e($v('price_presale', $event?->price_presale)) ?>" placeholder="z. B. 24 €" data-preview="price">
                    <?= $err('price_presale') ?>
                </div>
                <div class="field<?= $cls('price_box') ?>">
                    <label for="price_box">Abendkasse</label>
                    <input id="price_box" name="price_box" type="text" maxlength="60" value="<?= e($v('price_box', $event?->price_box)) ?>" placeholder="z. B. 29 €" data-preview="price">
                    <?= $err('price_box') ?>
                </div>
                <div class="field<?= $cls('age_rating') ?>">
                    <label for="age_rating">Altersfreigabe</label>
                    <input id="age_rating" name="age_rating" type="text" maxlength="40" value="<?= e($v('age_rating', $event?->age_rating)) ?>" placeholder="z. B. ab 18" list="age-suggestions">
                    <datalist id="age-suggestions"><option value="ab 16"><option value="ab 18"><option value="ab 21"><option value="Ü30"></datalist>
                </div>
            </div>
            <div class="field">
                <label for="ticket_status">Ticket-Status</label>
                <select id="ticket_status" name="ticket_status" data-preview="ticket_status">
                    <?php foreach (Event::TICKET_STATUS as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $v('ticket_status', $event?->ticket_status, 'available') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>
    </div>

    <aside class="form-side">
        <div class="side-sticky">
            <section class="panel">
                <h2 class="panel-title">Veröffentlichung</h2>
                <div class="segmented segmented-full">
                    <label><input type="radio" name="status" value="published"<?= $status !== 'draft' ? ' checked' : '' ?>><span><?= icon('eye') ?> Online</span></label>
                    <label><input type="radio" name="status" value="draft"<?= $status === 'draft' ? ' checked' : '' ?>><span><?= icon('eye-off') ?> Entwurf</span></label>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_featured" value="1"<?= $featured ? ' checked' : '' ?>>
                    <span class="switch-ui" aria-hidden="true"></span>
                    <span><strong>Highlight</strong><small>Groß im Slider der Startseite</small></span>
                </label>
                <div class="save-bar">
                    <button class="btn btn-accent btn-block" type="submit"><?= icon('check') ?> <?= $isEdit ? 'Änderungen speichern' : 'Veranstaltung speichern' ?></button>
                    <?php if (!$isEdit): ?>
                        <button class="btn btn-ghost btn-block" type="submit" name="after" value="new">Speichern &amp; weitere anlegen</button>
                    <?php endif; ?>
                </div>
                <?php if ($isEdit): ?>
                    <div class="side-links">
                        <a href="<?= e($event->url()) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Auf der Website ansehen</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel preview-panel">
                <h2 class="panel-title">Vorschau</h2>
                <div class="pv-card cat-<?= e($category) ?>" data-pv>
                    <div class="pv-media">
                        <img src="<?= e($imageUrl ?? '') ?>" alt="" data-pv-img<?= $imageUrl ? '' : ' hidden' ?> style="object-position: center <?= e($focus === 'center' ? 'center' : $focus) ?>">
                        <div class="pv-fallback" data-pv-fallback<?= $imageUrl ? ' hidden' : '' ?>><span data-pv-fallback-title></span></div>
                        <span class="pv-date"><b data-pv-wd>–</b><strong data-pv-day>–</strong><b data-pv-mon>–</b></span>
                        <span class="pv-ribbon" data-pv-ribbon hidden></span>
                    </div>
                    <div class="pv-body">
                        <span class="pill pill-cat"><span class="dot"></span><span data-pv-badge></span></span>
                        <strong class="pv-title" data-pv-title>Titel der Veranstaltung</strong>
                        <span class="pv-sub" data-pv-sub></span>
                        <span class="pv-meta" data-pv-meta></span>
                    </div>
                </div>
            </section>

            <?php if ($isEdit): ?>
                <p class="meta-note">Erstellt <?= e(date('d.m.Y H:i', strtotime($event->created_at))) ?> · Geändert <?= e(date('d.m.Y H:i', strtotime($event->updated_at))) ?></p>
            <?php endif; ?>
        </div>
    </aside>
</form>

<?php if ($isEdit): ?>
    <form class="danger-zone" method="post" action="<?= e(url('admin/events/' . $event->id . '/loeschen')) ?>" data-confirm="„<?= e($event->title) ?>“ wirklich löschen? Das kann nicht rückgängig gemacht werden.">
        <?= csrf_field() ?>
        <div>
            <strong>Veranstaltung löschen</strong>
            <p class="muted">Entfernt die Veranstaltung samt Bild dauerhaft.</p>
        </div>
        <button class="btn btn-danger" type="submit"><?= icon('trash') ?> Löschen</button>
    </form>
<?php endif; ?>
