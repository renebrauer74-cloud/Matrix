/* Matrix Bochum – Frontend-Interaktionen (ohne Abhängigkeiten) */
(() => {
    'use strict';
    document.documentElement.classList.add('js');

    const store = {
        get(k) { try { return localStorage.getItem(k); } catch { return null; } },
        set(k, v) { try { localStorage.setItem(k, v); } catch { /* privat-Modus */ } },
    };

    // --- Header beim Scrollen ----------------------------------------------------
    const header = document.querySelector('[data-header]');
    const onScroll = () => header && header.classList.toggle('is-scrolled', window.scrollY > 24);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    // --- Mobile Navigation ----------------------------------------------------------
    const toggle = document.querySelector('[data-nav-toggle]');
    if (toggle) {
        const setOpen = (open) => {
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        };
        toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('nav-open')));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
        document.querySelectorAll('#main-nav a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
        window.matchMedia('(min-width: 961px)').addEventListener('change', (m) => m.matches && setOpen(false));
    }

    // --- Hero-Karussell ---------------------------------------------------------------------
    // Folien liegen nebeneinander und gleiten horizontal. Für eine nahtlose Endlosschleife
    // werden erste und letzte Folie geklont. Das Weiterschalten steuert die CSS-Animation
    // des Fortschrittsbalkens (animationend) – so pausiert beides automatisch gemeinsam.
    const carousel = document.querySelector('[data-carousel]');
    const track = carousel && carousel.querySelector('[data-track]');
    if (track && track.children.length > 1) {
        const viewport = carousel.querySelector('[data-viewport]');
        const slides = [...track.children];
        const n = slides.length;
        const thumbs = [...carousel.querySelectorAll('[data-thumb]')];
        const counter = carousel.querySelector('[data-current]');
        const autoplay = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        carousel.style.setProperty('--slide-ms', (Number(carousel.dataset.interval) || 5) * 1000 + 'ms');
        carousel.classList.toggle('no-autoplay', !autoplay);

        const makeClone = (el) => {
            const c = el.cloneNode(true);
            c.classList.remove('is-active');
            c.classList.add('is-clone');
            c.setAttribute('aria-hidden', 'true');
            c.removeAttribute('aria-label');
            c.querySelectorAll('a, button').forEach((a) => { a.tabIndex = -1; });
            c.querySelectorAll('img').forEach((img) => { img.loading = 'eager'; });
            return c;
        };
        track.prepend(makeClone(slides[n - 1]));
        track.append(makeClone(slides[0]));
        const all = [...track.children];

        let pos = 1;          // Position in der Spur inkl. Klone
        let busy = false;     // Übergang läuft
        const real = () => (pos - 1 + n) % n;

        const place = (animate, offset = 0) => {
            track.classList.toggle('is-instant', !animate);
            track.style.transform = `translate3d(calc(${-pos * 100}% + ${offset}px), 0, 0)`;
        };

        const mark = () => {
            const r = real();
            all.forEach((s, i) => s.classList.toggle('is-active', i === pos));
            slides.forEach((s, i) => {
                s.setAttribute('aria-hidden', String(i !== r));
                s.querySelectorAll('a:not(.hero-poster), button').forEach((a) => { a.tabIndex = i === r ? 0 : -1; });
            });
            // Fortschrittsbalken neu starten
            thumbs.forEach((t) => { t.classList.remove('is-active'); t.removeAttribute('aria-current'); });
            void carousel.offsetWidth;
            if (thumbs[r]) { thumbs[r].classList.add('is-active'); thumbs[r].setAttribute('aria-current', 'true'); }
            if (counter) counter.textContent = String(r + 1).padStart(2, '0');
        };

        const go = (to) => {
            if (busy) return;
            busy = to !== pos;
            pos = to;
            place(true);
            mark();
        };
        const next = () => go(pos + 1);
        const prev = () => go(pos - 1);

        track.addEventListener('transitionend', (e) => {
            if (e.target !== track || e.propertyName !== 'transform') return;
            busy = false;
            if (pos === 0 || pos === n + 1) {
                // vom Klon unbemerkt auf die echte Folie springen
                pos = pos === 0 ? n : 1;
                track.classList.add('is-jump');
                place(false);
                all.forEach((s, i) => s.classList.toggle('is-active', i === pos));
                void track.offsetWidth;
                requestAnimationFrame(() => requestAnimationFrame(() => track.classList.remove('is-jump')));
            }
        });

        // Autoplay: Ende des Fortschrittsbalkens -> nächste Folie
        carousel.addEventListener('animationend', (e) => {
            if (autoplay && e.animationName === 'hero-progress') next();
        });

        // Pausieren nur beim Ziehen, bei Tastatur-Fokus oder verstecktem Tab
        const pause = { focus: false, drag: false, hidden: false };
        const syncPause = () => carousel.classList.toggle('is-paused', Object.values(pause).some(Boolean));
        carousel.addEventListener('focusin', (e) => { pause.focus = e.target.matches(':focus-visible'); syncPause(); });
        carousel.addEventListener('focusout', (e) => { if (!carousel.contains(e.relatedTarget)) { pause.focus = false; syncPause(); } });
        document.addEventListener('visibilitychange', () => { pause.hidden = document.hidden; syncPause(); });

        // Bedienelemente
        carousel.querySelector('[data-next]')?.addEventListener('click', next);
        carousel.querySelector('[data-prev]')?.addEventListener('click', prev);
        thumbs.forEach((t) => t.addEventListener('click', () => go(+t.dataset.thumb + 1)));
        carousel.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { e.preventDefault(); next(); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); }
        });

        // Ziehen / Wischen (Maus und Touch), die Folie folgt dem Finger
        let startX = 0, startY = 0, dx = 0, dragging = false, decided = false, moved = false;
        viewport.addEventListener('pointerdown', (e) => {
            if (busy || (e.pointerType === 'mouse' && e.button !== 0)) return;
            dragging = true; decided = false; moved = false;
            startX = e.clientX; startY = e.clientY; dx = 0;
        });
        window.addEventListener('pointermove', (e) => {
            if (!dragging) return;
            const mx = e.clientX - startX;
            const my = e.clientY - startY;
            if (!decided) {
                if (Math.abs(mx) < 8 && Math.abs(my) < 8) return;
                decided = true;
                if (Math.abs(my) > Math.abs(mx)) { dragging = false; return; } // vertikal scrollen
                moved = true;
                pause.drag = true; syncPause();
                carousel.classList.add('is-dragging');
            }
            dx = mx;
            place(false, dx);
        });
        const endDrag = () => {
            if (!dragging) return;
            dragging = false;
            carousel.classList.remove('is-dragging');
            pause.drag = false; syncPause();
            if (!moved) return;
            const threshold = Math.min(120, viewport.offsetWidth * 0.15);
            if (dx < -threshold) next();
            else if (dx > threshold) prev();
            else place(true);
        };
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);
        // Klick nach dem Ziehen nicht als Link-Klick werten
        viewport.addEventListener('click', (e) => {
            if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
        }, true);
        viewport.addEventListener('dragstart', (e) => e.preventDefault());

        place(false);
        mark();
    }

    // --- Kategorie-Filter (Startseite) -----------------------------------------------
    const filter = document.querySelector('[data-filter]');
    const list = document.querySelector('[data-events]');
    if (filter && list) {
        const limit = +filter.dataset.limit || 9;
        const cards = [...list.querySelectorAll('.event-card')];
        const empty = document.querySelector('[data-empty]');
        const more = document.querySelector('[data-more-link]');
        const slugs = { konzert: 'konzerte', party: 'partys', sonstiges: 'sonstiges' };

        filter.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-filter-value]');
            if (!btn) return;
            const value = btn.dataset.filterValue;
            filter.querySelectorAll('.chip').forEach((c) => {
                c.classList.toggle('is-active', c === btn);
                c.setAttribute('aria-selected', String(c === btn));
            });
            let shown = 0;
            cards.forEach((card) => {
                const match = value === 'all' || card.dataset.category === value;
                const visible = match && shown < limit;
                card.hidden = !visible;
                if (visible) shown++;
            });
            if (empty) empty.hidden = shown > 0;
            if (more) more.href = more.dataset.base + (value !== 'all' ? '/' + slugs[value] : '');
        });
    }

    // --- Ansicht Kacheln / Liste -------------------------------------------------------
    const targets = document.querySelectorAll('[data-view-target]');
    const toggles = document.querySelectorAll('.view-toggle');
    if (targets.length && toggles.length) {
        const apply = (view) => {
            targets.forEach((t) => t.classList.toggle('is-list', view === 'list'));
            toggles.forEach((g) => g.querySelectorAll('[data-view]').forEach((b) => {
                const on = b.dataset.view === view;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', String(on));
            }));
        };
        apply(store.get('matrix-view') || 'grid');
        toggles.forEach((g) => g.addEventListener('click', (e) => {
            const b = e.target.closest('[data-view]');
            if (!b) return;
            store.set('matrix-view', b.dataset.view);
            apply(b.dataset.view);
        }));
    }

    // --- Genre-/Raum-Filter sofort absenden (leere Felder nicht in die URL) ------------------
    document.querySelectorAll('[data-autosubmit] select').forEach((s) => {
        s.addEventListener('change', () => {
            s.form.querySelectorAll('select').forEach((el) => { el.disabled = !el.value; });
            s.form.submit();
        });
    });

    // --- YouTube erst nach Klick laden (Datenschutz) ---------------------------------------
    document.querySelectorAll('[data-yt]').forEach((box) => {
        box.querySelector('.yt-play').addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(box.dataset.yt)}?autoplay=1&rel=0`;
            iframe.title = box.dataset.title || 'Video';
            iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            iframe.allowFullscreen = true;
            box.replaceChildren(iframe);
            box.classList.add('is-playing');
        });
    });

    // --- Teilen ---------------------------------------------------------------------------
    document.querySelectorAll('[data-share]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const data = { title: btn.dataset.title, url: btn.dataset.url };
            if (navigator.share) {
                try { await navigator.share(data); } catch { /* abgebrochen */ }
                return;
            }
            try {
                await navigator.clipboard.writeText(data.url);
                const label = btn.innerHTML;
                btn.textContent = 'Link kopiert ✓';
                setTimeout(() => { btn.innerHTML = label; }, 2000);
            } catch {
                window.prompt('Link kopieren:', data.url);
            }
        });
    });

    // --- Formulare: doppeltes Absenden verhindern ------------------------------------------
    document.querySelectorAll('[data-site-form]').forEach((form) => form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = true; });
    }));

    // --- Galerie mit Lightbox --------------------------------------------------------------
    const dialog = document.querySelector('[data-lightbox-dialog]');
    const links = [...document.querySelectorAll('[data-lightbox]')];
    if (dialog && links.length && typeof dialog.showModal === 'function') {
        const img = dialog.querySelector('[data-lightbox-img]');
        let current = 0;
        const show = (i) => {
            current = (i + links.length) % links.length;
            img.src = links[current].href;
            img.alt = links[current].querySelector('img')?.alt || '';
        };
        links.forEach((a, i) => a.addEventListener('click', (e) => {
            e.preventDefault();
            show(i);
            dialog.showModal();
        }));
        dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(current - 1));
        dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => show(current + 1));
        dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
        dialog.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') show(current - 1);
            if (e.key === 'ArrowRight') show(current + 1);
        });
    }

    // --- Einblenden beim Scrollen -----------------------------------------------------------
    if ('IntersectionObserver' in window) {
        const els = document.querySelectorAll('.section-head, .event-card, .room-tile, .room, .split, .ticket-band, .month-title, .job-card, .feature, .gallery-item');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                if (!en.isIntersecting) return;
                const el = en.target;
                el.classList.add('is-visible');
                io.unobserve(el);
                // danach Klassen entfernen, damit Hover-Transforms wieder greifen
                setTimeout(() => el.classList.remove('reveal', 'is-visible'), 800);
            });
        }, { rootMargin: '0px 0px -40px 0px' });
        els.forEach((el) => { el.classList.add('reveal'); io.observe(el); });
    }
})();
