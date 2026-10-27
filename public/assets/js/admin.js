/* Matrix Bochum – Admin-Interaktionen */
(() => {
    'use strict';
    document.documentElement.classList.add('js');
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const caps = (t) => String(t).replace(/ß/g, 'ẞ');

    // --- Sidebar (mobil) ------------------------------------------------------------
    const sbToggle = $('[data-sidebar-toggle]');
    const setSidebar = (open) => {
        document.body.classList.toggle('sidebar-open', open);
        sbToggle?.setAttribute('aria-expanded', String(open));
    };
    sbToggle?.addEventListener('click', () => setSidebar(!document.body.classList.contains('sidebar-open')));
    $('[data-sidebar-close]')?.addEventListener('click', () => setSidebar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

    // --- Meldungen ---------------------------------------------------------------------
    $$('[data-dismiss]').forEach((a) => {
        a.querySelector('.alert-close')?.addEventListener('click', () => a.remove());
        if (a.classList.contains('alert-success')) setTimeout(() => a.remove(), 6000);
    });

    // --- Sicherheitsabfragen (am Formular oder am einzelnen Button) -----------------------
    document.addEventListener('submit', (e) => {
        const message = e.submitter?.dataset.confirm || e.target.dataset.confirm;
        if (message && !window.confirm(message)) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);

    // --- Aktionsmenüs: nur eines offen ----------------------------------------------------
    document.addEventListener('click', (e) => {
        $$('details.menu[open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });

    // --- Einfache Upload-Felder (Seiten, Galerie): gewählte Dateien anzeigen --------------
    $$('[data-upload]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const names = zone.querySelector('[data-upload-names]');
        const show = () => {
            const files = [...input.files];
            names.textContent = files.length > 3 ? files.length + ' Dateien ausgewählt' : files.map((f) => f.name).join(', ');
            zone.classList.toggle('is-over', files.length > 0);
        };
        input.addEventListener('change', show);
        ['dragenter', 'dragover'].forEach((t) => zone.addEventListener(t, () => zone.classList.add('is-over')));
        zone.addEventListener('dragleave', () => zone.classList.toggle('is-over', input.files.length > 0));
        zone.addEventListener('drop', () => setTimeout(show, 0));
        zone.closest('form')?.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            $$('button[type="submit"]', e.target).forEach((b) => { b.disabled = true; });
        });
    });

    // --- Social-Media-Links: Zeilen hinzufügen und entfernen -----------------------------
    const PLATFORM_HOSTS = { instagram: 'instagram.com', facebook: 'facebook.com', tiktok: 'tiktok.com', youtube: 'youtube.com' };
    const initSocialRow = (row) => {
        const select = row.querySelector('[data-social-platform]');
        const url = row.querySelector('[data-social-url]');
        const setPlaceholder = () => { url.placeholder = select.selectedOptions[0]?.dataset.placeholder || ''; };
        select.addEventListener('change', setPlaceholder);
        // Eingefügten Link der passenden Plattform zuordnen
        url.addEventListener('input', () => {
            const host = url.value.toLowerCase();
            const match = Object.keys(PLATFORM_HOSTS).find((p) => host.includes(PLATFORM_HOSTS[p]) || (p === 'youtube' && host.includes('youtu.be')) || (p === 'facebook' && host.includes('fb.com')));
            if (match && select.value !== match) { select.value = match; setPlaceholder(); }
        });
    };
    $$('[data-social-links]').forEach((box) => {
        const rows = box.querySelector('[data-social-rows]');
        const empty = box.querySelector('[data-social-empty]');
        const template = box.querySelector('[data-social-template]');
        let next = Number(box.dataset.nextIndex) || 0;
        const refresh = () => { empty.hidden = rows.children.length > 0; };
        rows.querySelectorAll('[data-social-row]').forEach(initSocialRow);
        box.querySelector('[data-social-add]').addEventListener('click', () => {
            const html = template.innerHTML.replaceAll('__INDEX__', String(next++));
            rows.insertAdjacentHTML('beforeend', html);
            const row = rows.lastElementChild;
            initSocialRow(row);
            row.querySelector('[data-social-url]').focus();
            refresh();
        });
        rows.addEventListener('click', (e) => {
            const remove = e.target.closest('[data-social-remove]');
            if (!remove) return;
            remove.closest('[data-social-row]').remove();
            refresh();
            rows.closest('form')?.dispatchEvent(new Event('change'));
        });
    });

    // --- Event-Formular ----------------------------------------------------------------
    const form = $('[data-event-form]');
    if (!form) return;

    const WD = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    const WD_LONG = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    const MON = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    const CAT = { konzert: 'Konzert', party: 'Party', sonstiges: 'Sonstiges' };
    const RIBBON = { few: 'Wenige Tickets', almostsoldout: 'Fast ausverkauft', soldout: 'Ausverkauft', cancelled: 'Abgesagt', postponed: 'Verlegt', free: 'Eintritt frei' };
    const val = (id) => (document.getElementById(id) || {}).value || '';
    const pad = (n) => String(n).padStart(2, '0');
    const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parseDate = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d, 12); };
    const shortDate = (d) => `${WD[d.getDay()]}, ${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${String(d.getFullYear()).slice(2)}`;

    const pv = $('[data-pv]');
    const labelField = $('[data-label-field]');
    const labelInput = $('#category_label');
    const currentCat = () => ($('input[name="category"]:checked') || {}).value || 'konzert';

    // Art: bei „Sonstiges“ erscheint das Pflichtfeld „Was für eine Veranstaltung?“
    const updateCategory = () => {
        const other = currentCat() === 'sonstiges';
        labelField.hidden = !other;
        labelInput.required = other;
        if (pv) pv.className = 'pv-card cat-' + currentCat();
        updatePreview();
    };
    $$('[data-cat-input]').forEach((r) => r.addEventListener('change', () => {
        updateCategory();
        if (currentCat() === 'sonstiges' && !labelInput.value) labelInput.focus();
    }));

    // Veranstalter: Matrix oder extern – bei extern Stammdaten anzeigen
    const organizerFields = $('[data-organizer-fields]');
    const organizerSelect = $('[data-organizer-select]');
    const updateOrganizer = () => {
        const external = ($('input[name="organizer"]:checked') || {}).value === 'external';
        organizerFields.hidden = !external;
    };
    $$('input[name="organizer"]').forEach((r) => r.addEventListener('change', () => {
        updateOrganizer();
        if (!organizerFields.hidden) (organizerSelect?.value ? organizerSelect : $('#organizer_name')).focus();
    }));
    // Gespeicherten Veranstalter gewählt: Stammdaten eintragen, „Neuer Veranstalter“: Felder leeren
    organizerSelect?.addEventListener('change', () => {
        const data = JSON.parse(organizerSelect.selectedOptions[0]?.dataset.organizer || '{}');
        $$('[data-organizer-field]').forEach((input) => { input.value = data[input.dataset.organizerField] || ''; });
        const subtitle = $('[data-organizer-subtitle]');
        if (subtitle) subtitle.textContent = organizerSelect.value ? subtitle.dataset.textExisting : subtitle.dataset.textNew;
        if (!organizerSelect.value) $('#organizer_name').focus();
    });
    if (organizerFields) updateOrganizer();

    // Veröffentlichung „Geplant“: Datum und Uhrzeit einblenden
    const publishAt = $('[data-publish-at]');
    $$('[data-publication-input]').forEach((r) => r.addEventListener('change', () => {
        publishAt.hidden = r.value !== 'scheduled';
        if (!publishAt.hidden && !$('#publish_date').value) $('#publish_date').focus();
    }));

    // Schnell-Ausfüllen (Ticket-Links)
    $$('[data-fill]').forEach((b) => b.addEventListener('click', () => {
        const input = document.getElementById(b.dataset.fill);
        input.value = b.dataset.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }));

    // --- Genres: Mehrfachauswahl, neue direkt eintippen ---------------------------------
    const gp = $('[data-genre-picker]');
    const gpInput = $('[data-gp-input]');
    const gpMenu = $('[data-gp-menu]');
    const gpChips = $('[data-gp-chips]');
    const gpOptions = $('[data-gp-options]');
    const gpAdd = $('[data-gp-add]');
    const gpEmpty = $('[data-gp-empty]');
    const norm = (s) => s.toLocaleLowerCase('de').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ß|ẞ/g, 'ss').trim();
    const options = () => $$('.gp-option', gpOptions);
    const optName = (o) => o.querySelector('.gp-name').textContent;
    const checkedOptions = () => options().filter((o) => o.querySelector('input').checked);

    const openMenu = (open) => {
        gpMenu.classList.toggle('is-open', open);
        gpInput.setAttribute('aria-expanded', String(open));
    };
    const renderChips = () => {
        gpChips.innerHTML = '';
        checkedOptions().forEach((o) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'gp-chip';
            chip.innerHTML = '<span></span><b aria-hidden="true">×</b>';
            chip.querySelector('span').textContent = optName(o);
            chip.setAttribute('aria-label', optName(o) + ' entfernen');
            chip.addEventListener('click', () => { o.querySelector('input').checked = false; syncGenres(); gpInput.focus(); });
            gpChips.appendChild(chip);
        });
    };
    const filterOptions = () => {
        const q = norm(gpInput.value);
        let exact = false;
        let visible = 0;
        options().forEach((o) => {
            const name = norm(optName(o));
            o.hidden = q !== '' && !name.includes(q);
            if (!o.hidden) visible++;
            if (name === q) exact = true;
        });
        gpAdd.hidden = !q || exact;
        gpAdd.querySelector('span').textContent = `„${gpInput.value.trim()}“ als neues Genre hinzufügen`;
        gpEmpty.hidden = visible > 0 || q !== '';
    };
    function syncGenres() { renderChips(); filterOptions(); updatePreview(); }
    const addGenre = (name) => {
        name = name.trim().replace(/\s+/g, ' ');
        if (!name) return;
        const existing = options().find((o) => norm(optName(o)) === norm(name));
        if (existing) {
            existing.querySelector('input').checked = true;
        } else {
            const label = document.createElement('label');
            label.className = 'gp-option is-new';
            label.innerHTML = '<input type="checkbox" name="new_genres[]" checked><span class="gp-check" aria-hidden="true"></span><span class="gp-name"></span><small>neu</small>';
            label.querySelector('input').value = name;
            label.querySelector('.gp-name').textContent = name;
            label.querySelector('.gp-check').innerHTML = gpOptions.querySelector('.gp-check')?.innerHTML || '✓';
            gpOptions.prepend(label);
        }
        gpInput.value = '';
        syncGenres();
    };

    if (gp) {
        gpOptions.addEventListener('change', syncGenres);
        gpInput.addEventListener('focus', () => openMenu(true));
        gpInput.addEventListener('input', () => { openMenu(true); filterOptions(); });
        gpInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const q = norm(gpInput.value);
                if (!q) return;
                // Enter: exakter Treffer > einziger Treffer > als neues Genre anlegen
                const visible = options().filter((o) => !o.hidden);
                const match = visible.find((o) => norm(optName(o)) === q) || (visible.length === 1 ? visible[0] : null);
                if (match) {
                    match.querySelector('input').checked = true;
                    gpInput.value = '';
                    syncGenres();
                } else {
                    addGenre(gpInput.value);
                }
            } else if (e.key === 'Backspace' && !gpInput.value) {
                const last = checkedOptions().pop();
                if (last) { last.querySelector('input').checked = false; syncGenres(); }
            } else if (e.key === 'Escape') {
                openMenu(false);
            }
        });
        gpAdd.addEventListener('click', () => { addGenre(gpInput.value); gpInput.focus(); });
        $('[data-gp-toggle]').addEventListener('click', () => {
            const open = !gpMenu.classList.contains('is-open');
            openMenu(open);
            if (open) gpInput.focus();
        });
        $('[data-gp-box]').addEventListener('click', (e) => { if (e.target === e.currentTarget) gpInput.focus(); });
        document.addEventListener('click', (e) => { if (!gp.contains(e.target)) openMenu(false); });
        renderChips();
        filterOptions();
    }

    // --- Wiederholung -------------------------------------------------------------------
    const repeatSelect = $('[data-repeat]');
    const repeatPreview = $('[data-repeat-preview]');
    const repeatDatesBox = $('[data-repeat-dates]');
    const repeatSummary = $('[data-repeat-summary]');
    const repeatEndBox = $('[data-repeat-end]');
    const repeatCustom = $('[data-repeat-custom]');
    const countInput = $('[data-repeat-count]');
    const untilInput = $('[data-repeat-until]');
    let customDates = new Set();
    let unchecked = new Set();

    // Gleiche Rechnung wie App\Actions\Events\CalculateRecurrenceDatesAction
    function computeDates(startStr, rule, count, untilStr) {
        const start = parseDate(startStr);
        const until = untilStr ? parseDate(untilStr) : null;
        const day = start.getDate();
        const wd = start.getDay();
        const nth = Math.ceil(day / 7);
        const isLast = new Date(start.getFullYear(), start.getMonth(), day + 7).getMonth() !== start.getMonth();
        const out = [];
        for (let i = 1; i <= 208 && out.length < 104; i++) {
            let d = null;
            const y = start.getFullYear();
            const m = start.getMonth() + i;
            if (rule === 'weekly') d = new Date(start.getFullYear(), start.getMonth(), day + 7 * i, 12);
            else if (rule === 'biweekly') d = new Date(start.getFullYear(), start.getMonth(), day + 14 * i, 12);
            else if (rule === 'monthly_date') {
                if (day <= new Date(y, m + 1, 0).getDate()) d = new Date(y, m, day, 12);
            } else if (rule === 'monthly_weekday') {
                if (isLast) {
                    const last = new Date(y, m + 1, 0, 12);
                    d = new Date(y, m, last.getDate() - ((last.getDay() - wd + 7) % 7), 12);
                } else {
                    const date = 1 + ((wd - new Date(y, m, 1).getDay() + 7) % 7) + (nth - 1) * 7;
                    if (date <= new Date(y, m + 1, 0).getDate()) d = new Date(y, m, date, 12);
                }
            }
            if (!d) continue;
            if (until ? d > until : out.length >= count - 1) break;
            out.push(ymd(d));
        }
        return out;
    }

    function updateRepeatLabels() {
        const start = val('date') ? parseDate(val('date')) : null;
        const weekdayOption = repeatSelect.querySelector('option[value="monthly_weekday"]');
        const dateOption = repeatSelect.querySelector('option[value="monthly_date"]');
        if (!start) return;
        const isLast = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 7).getMonth() !== start.getMonth();
        weekdayOption.textContent = `Jeden ${isLast ? 'letzten' : Math.ceil(start.getDate() / 7) + '.'} ${WD_LONG[start.getDay()]} im Monat`;
        dateOption.textContent = `Jeden Monat am ${start.getDate()}.`;
    }

    function summarize() {
        const extra = $$('input[name="repeat_dates[]"]:checked', repeatDatesBox).length;
        const editing = form.querySelector('input[name="_method"]') !== null;
        repeatSummary.textContent = extra
            ? (editing ? `${extra} neue Termine werden zusätzlich angelegt:` : `${extra + 1} Termine insgesamt – dieser + ${extra} weitere:`)
            : (repeatSelect.value === 'custom' ? 'Wähle oben weitere Daten aus.' : 'Keine weiteren Termine ausgewählt.');
    }

    function renderRepeat() {
        updateRepeatLabels();
        const rule = repeatSelect.value;
        const byUntil = ($('input[name="repeat_end"]:checked') || {}).value === 'until';
        repeatPreview.hidden = rule === 'none';
        repeatEndBox.hidden = rule === 'none' || rule === 'custom';
        repeatCustom.hidden = rule !== 'custom';
        countInput.hidden = byUntil;
        untilInput.hidden = !byUntil;
        if (rule === 'none') return;
        if (!val('date')) {
            repeatSummary.textContent = 'Bitte zuerst oben das Datum wählen.';
            repeatDatesBox.innerHTML = '';
            return;
        }
        const startStr = val('date');
        const dates = rule === 'custom'
            ? [...customDates].filter((d) => d > startStr).sort()
            : computeDates(startStr, rule, Math.max(2, Math.min(52, Number(countInput.value) || 2)), byUntil ? untilInput.value : '');

        repeatDatesBox.innerHTML = '';
        const first = document.createElement('span');
        first.className = 'repeat-chip is-first';
        first.textContent = shortDate(parseDate(startStr));
        first.title = 'Dieser Termin';
        repeatDatesBox.appendChild(first);
        dates.forEach((d) => {
            const label = document.createElement('label');
            label.className = 'repeat-chip';
            label.innerHTML = '<input type="checkbox" name="repeat_dates[]"><span></span>';
            const box = label.querySelector('input');
            box.value = d;
            box.checked = !unchecked.has(d);
            label.querySelector('span').textContent = shortDate(parseDate(d));
            box.addEventListener('change', () => {
                if (box.checked) unchecked.delete(d); else unchecked.add(d);
                if (rule === 'custom' && !box.checked) customDates.delete(d);
                summarize();
            });
            repeatDatesBox.appendChild(label);
        });
        summarize();
    }

    if (repeatSelect) {
        const flag = document.createElement('input');
        flag.type = 'hidden';
        flag.name = 'repeat_js';
        flag.value = '1';
        form.appendChild(flag);

        // Nach einem Validierungsfehler die zuvor gewählten Termine wiederherstellen
        const oldDates = JSON.parse(repeatPreview.dataset.old || '[]');
        if (oldDates.length) {
            if (repeatSelect.value === 'custom') {
                customDates = new Set(oldDates);
            } else if (val('date')) {
                const all = computeDates(val('date'), repeatSelect.value, Number(countInput.value) || 2, untilInput.value);
                unchecked = new Set(all.filter((d) => !oldDates.includes(d)));
            }
        }
        repeatSelect.addEventListener('change', () => { unchecked.clear(); renderRepeat(); });
        $$('input[name="repeat_end"]').forEach((r) => r.addEventListener('change', renderRepeat));
        countInput.addEventListener('input', renderRepeat);
        untilInput.addEventListener('change', renderRepeat);
        $('#date').addEventListener('change', () => { unchecked.clear(); renderRepeat(); updateFeatured(); });
        $('[data-repeat-add]').addEventListener('click', () => {
            const pick = $('[data-repeat-pick]');
            if (!pick.value) { pick.focus(); return; }
            if (val('date') && pick.value <= val('date')) { window.alert('Bitte ein Datum nach dem ersten Termin wählen.'); return; }
            customDates.add(pick.value);
            unchecked.delete(pick.value);
            pick.value = '';
            renderRepeat();
        });
        renderRepeat();
    }

    // --- Highlight-Zeitraum ------------------------------------------------------------
    const featuredToggle = $('[data-featured-toggle]');
    const featuredWindow = $('[data-featured-window]');
    const featuredStart = $('[data-featured-start]');
    const featuredEnd = $('[data-featured-end]');
    const featuredFrom = $('[data-featured-from]');
    const featuredUntil = $('[data-featured-until]');
    const featuredSummary = $('[data-featured-summary]');
    function updateFeatured() {
        if (!featuredToggle) return;
        featuredWindow.hidden = !featuredToggle.checked;
        featuredFrom.hidden = featuredStart.value !== 'custom';
        featuredUntil.hidden = featuredEnd.value !== 'custom';
        const at = (date, time) => (date ? `${shortDate(parseDate(date))} ${time || '00:00'}` : '');
        let from = 'ab sofort';
        if (featuredStart.value === 'custom') {
            const date = $('input[name="featured_from_date"]').value;
            if (date) from = 'ab ' + at(date, $('input[name="featured_from_time"]').value);
        } else if (/^\d+$/.test(featuredStart.value) && val('date')) {
            const d = parseDate(val('date'));
            d.setDate(d.getDate() - Number(featuredStart.value));
            from = 'ab ' + at(ymd(d), val('time'));
        }
        const untilDate = $('input[name="featured_until_date"]').value;
        const until = featuredEnd.value === 'custom' && untilDate ? 'bis ' + at(untilDate, $('input[name="featured_until_time"]').value) : 'bis zur Veranstaltung';
        featuredSummary.textContent = `Ganz vorne im Karussell ${from} ${until}.`;
    }
    featuredWindow?.addEventListener('change', updateFeatured);
    featuredToggle?.addEventListener('change', updateFeatured);
    $('#time')?.addEventListener('change', updateFeatured);
    updateFeatured();

    // --- Bild-Upload mit Vorschau ---------------------------------------------------
    const dz = $('[data-dropzone]');
    const dzInput = $('[data-dropzone-input]');
    const dzImg = $('[data-dropzone-img]');
    const imageOptions = $('[data-image-options]');
    const removeBox = $('[data-remove-image]');
    const pvImg = $('[data-pv-img]');
    const pvFallback = $('[data-pv-fallback]');

    const setImage = (src) => {
        dzImg.src = src || '';
        dzImg.hidden = !src;
        dz.classList.toggle('has-image', !!src);
        imageOptions.hidden = !src;
        if (pvImg) { pvImg.src = src || ''; pvImg.hidden = !src; pvFallback.hidden = !!src; }
    };

    const handleFile = (file) => {
        if (!file) return;
        if (!/^image\//.test(file.type)) { alert('Bitte eine Bilddatei auswählen.'); dzInput.value = ''; return; }
        const max = 12 * 1024 * 1024;
        if (file.size > max) { alert('Das Bild ist größer als 12 MB.'); dzInput.value = ''; return; }
        const reader = new FileReader();
        reader.onload = () => setImage(reader.result);
        reader.readAsDataURL(file);
        if (removeBox) { removeBox.checked = false; dz.classList.remove('is-removed'); }
    };
    dzInput?.addEventListener('change', () => handleFile(dzInput.files[0]));
    ['dragenter', 'dragover'].forEach((t) => dz?.addEventListener(t, (e) => { e.preventDefault(); dz.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((t) => dz?.addEventListener(t, () => dz.classList.remove('is-over')));
    dz?.addEventListener('drop', (e) => {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        dzInput.files = dt.files;
        handleFile(file);
    });
    removeBox?.addEventListener('change', () => {
        dz.classList.toggle('is-removed', removeBox.checked);
        if (pvImg) { pvImg.hidden = removeBox.checked; pvFallback.hidden = !removeBox.checked; }
    });

    // --- Video: YouTube-Link prüfen, Datei anzeigen --------------------------------------
    const ytInput = $('[data-yt-input]');
    const ytPreview = $('[data-yt-preview]');
    const youtubeId = (url) => (url.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/) || [])[1];
    const updateYoutube = () => {
        const id = youtubeId(ytInput.value);
        ytPreview.hidden = !id;
        if (id) $('[data-yt-thumb]').src = `https://i.ytimg.com/vi/${id}/mqdefault.jpg`;
        ytInput.closest('.field').classList.toggle('has-error', !!ytInput.value.trim() && !id);
    };
    ytInput?.addEventListener('input', updateYoutube);
    if (ytInput?.value) updateYoutube();

    const videoInput = $('[data-video-input]');
    videoInput?.addEventListener('change', () => {
        const file = videoInput.files[0];
        if (!file) return;
        if (!/^video\/(mp4|webm|quicktime)$/.test(file.type)) { alert('Bitte ein MP4-, WebM- oder MOV-Video auswählen.'); videoInput.value = ''; return; }
        $('[data-video-name]').textContent = `${file.name} · ${(file.size / 1048576).toFixed(1)} MB – wird beim Speichern hochgeladen`;
    });

    // --- Live-Vorschau -----------------------------------------------------------------
    function updatePreview() {
        if (!pv) return;
        const cat = currentCat();
        const title = caps(val('title').trim() || 'Titel der Veranstaltung');
        $('[data-pv-title]').textContent = title;
        $('[data-pv-fallback-title]').textContent = title;
        $('[data-pv-sub]').textContent = val('subtitle');
        $('[data-pv-badge]').textContent = caps(cat === 'sonstiges' && labelInput.value.trim() ? labelInput.value.trim() : CAT[cat]);
        if (gp) $('[data-pv-genres]').textContent = checkedOptions().map(optName).join(' · ');

        const d = val('date') ? parseDate(val('date')) : null;
        $('[data-pv-wd]').textContent = d ? WD[d.getDay()] : '–';
        $('[data-pv-day]').textContent = d ? pad(d.getDate()) : '–';
        $('[data-pv-mon]').textContent = d ? MON[d.getMonth()] : '–';

        const rooms = $$('[data-room-input]').map((i) => (i.type === 'checkbox' ? (i.checked ? i.value : '') : i.value.trim())).filter(Boolean).join(', ');
        $('[data-pv-meta]').textContent = [val('time') ? val('time') + ' Uhr' : '', rooms].filter(Boolean).join(' · ');

        const ts = val('ticket_status');
        const rib = $('[data-pv-ribbon]');
        rib.hidden = !RIBBON[ts];
        rib.textContent = RIBBON[ts] || '';

        const focus = ($('input[name="image_focus"]:checked') || {}).value || 'center';
        if (pvImg) pvImg.style.objectPosition = 'center ' + focus;
    }
    form.addEventListener('input', updatePreview);
    form.addEventListener('change', updatePreview);
    updateCategory();

    // --- Absenden / ungespeicherte Änderungen ---------------------------------------------
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', (e) => {
        // Pflichtfeld "Sonstiges" clientseitig prüfen
        if (currentCat() === 'sonstiges' && !labelInput.value.trim()) {
            e.preventDefault();
            labelField.classList.add('has-error');
            labelInput.focus();
            return;
        }
        // Eingetipptes, noch nicht bestätigtes Genre mitnehmen
        if (gpInput && gpInput.value.trim()) addGenre(gpInput.value);
        dirty = false;
        $$('button[type="submit"]', form).forEach((b) => { b.disabled = true; });
        // gewählter Submit-Button (name="after") trotzdem übermitteln
        if (e.submitter && e.submitter.name) {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = e.submitter.name; h.value = e.submitter.value;
            form.appendChild(h);
        }
        if (videoInput?.files.length) (e.submitter || $('button[type="submit"]', form)).textContent = 'Video wird hochgeladen …';
    });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
