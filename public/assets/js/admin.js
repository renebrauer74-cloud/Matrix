/* Matrix Bochum – Admin-Interaktionen */
(() => {
    'use strict';
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

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

    // --- Sicherheitsabfragen ---------------------------------------------------------------
    $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
        if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    }));

    // --- Aktionsmenüs: nur eines offen ----------------------------------------------------
    document.addEventListener('click', (e) => {
        $$('details.menu[open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });

    // --- Event-Formular ----------------------------------------------------------------
    const form = $('[data-event-form]');
    if (!form) return;

    const WD = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    const MON = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    const CAT = { konzert: 'Konzert', party: 'Party', sonstiges: 'Sonstiges' };
    const RIBBON = { few: 'Wenige Tickets', soldout: 'Ausverkauft', cancelled: 'Abgesagt', postponed: 'Verlegt', free: 'Eintritt frei' };

    const pv = $('[data-pv]');
    const labelField = $('[data-label-field]');
    const labelInput = $('#category_label');
    const labelText = $('[data-label-text]');

    const currentCat = () => ($('input[name="category"]:checked') || {}).value || 'konzert';

    // Kategorie: bei "Sonstiges" wird das Eingabefeld zur Pflicht
    const updateCategory = () => {
        const cat = currentCat();
        const other = cat === 'sonstiges';
        labelInput.required = other;
        labelText.textContent = other ? labelText.dataset.textSonstiges : labelText.dataset.textDefault;
        labelInput.placeholder = labelInput.dataset['ph' + cat.charAt(0).toUpperCase() + cat.slice(1)] || '';
        labelField.classList.toggle('field-highlight', other);
        if (pv) pv.className = 'pv-card cat-' + cat;
        updatePreview();
    };
    $$('[data-cat-input]').forEach((r) => r.addEventListener('change', () => {
        updateCategory();
        if (currentCat() === 'sonstiges' && !labelInput.value) labelInput.focus();
    }));

    // Raum "Anderer Ort"
    const roomSelect = $('[data-room-select]');
    const roomCustom = $('[data-room-custom]');
    roomSelect?.addEventListener('change', () => {
        const custom = roomSelect.value === '__custom';
        roomCustom.hidden = !custom;
        if (custom) $('#room_custom').focus();
        updatePreview();
    });

    // Schnell-Ausfüllen (Ticket-Links)
    $$('[data-fill]').forEach((b) => b.addEventListener('click', () => {
        const input = document.getElementById(b.dataset.fill);
        input.value = b.dataset.value;
        input.dispatchEvent(new Event('input'));
    }));

    // --- Bild-Upload mit Vorschau ---------------------------------------------------
    const dz = $('[data-dropzone]');
    const dzInput = $('[data-dropzone-input]');
    const dzImg = $('[data-dropzone-img]');
    const options = $('[data-image-options]');
    const removeBox = $('[data-remove-image]');
    const pvImg = $('[data-pv-img]');
    const pvFallback = $('[data-pv-fallback]');

    const setImage = (src) => {
        dzImg.src = src || '';
        dzImg.hidden = !src;
        dz.classList.toggle('has-image', !!src);
        options.hidden = !src;
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

    // --- Live-Vorschau -----------------------------------------------------------------
    function updatePreview() {
        if (!pv) return;
        const val = (id) => (document.getElementById(id) || {}).value || '';
        const cat = currentCat();
        const title = val('title').trim() || 'Titel der Veranstaltung';
        $('[data-pv-title]').textContent = title;
        $('[data-pv-fallback-title]').textContent = title;
        $('[data-pv-sub]').textContent = val('subtitle');
        $('[data-pv-badge]').textContent = cat === 'sonstiges' && labelInput.value.trim() ? labelInput.value.trim() : CAT[cat];

        const d = val('date') ? new Date(val('date') + 'T12:00:00') : null;
        $('[data-pv-wd]').textContent = d ? WD[d.getDay()] : '–';
        $('[data-pv-day]').textContent = d ? String(d.getDate()).padStart(2, '0') : '–';
        $('[data-pv-mon]').textContent = d ? MON[d.getMonth()] : '–';

        let room = roomSelect.value === '__custom' ? val('room_custom') : roomSelect.value;
        const meta = [val('time') ? val('time') + ' Uhr' : '', room].filter(Boolean).join(' · ');
        $('[data-pv-meta]').textContent = meta;

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

    // --- Ungespeicherte Änderungen ------------------------------------------------------
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
        dirty = false;
        $$('button[type="submit"]', form).forEach((b) => { b.disabled = true; });
        // gewählter Submit-Button (name="after") trotzdem übermitteln
        if (e.submitter && e.submitter.name) {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = e.submitter.name; h.value = e.submitter.value;
            form.appendChild(h);
        }
    });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
