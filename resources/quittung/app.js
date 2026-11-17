/* Quittungsprogramm – Firmen, Kontakte, Mitarbeiter und Quittungen liegen im gemeinsamen Konto auf dem Server.
   Die PDFs entstehen im Browser und werden hochgeladen. */
'use strict';

const APP_VERSION = '2.0.0';

/* =========================================================
   Hilfsfunktionen: Geld, Datum, Zahl in Worten
   ========================================================= */

/** "1.234,56" | "1234.56" | "12" -> Cent (Integer) oder NaN */
function parseEuro(str) {
  let s = String(str || '').replace(/[€\s]/g, '').replace(/eur/i, '');
  if (!s) return NaN;
  if (s.includes(',')) {
    s = s.replace(/\./g, '').replace(',', '.');
  } else if ((s.match(/\./g) || []).length > 1) {
    s = s.replace(/\./g, '');
  } else if (/^\d{1,3}\.\d{3}$/.test(s)) {
    s = s.replace('.', ''); // "1.500" = 1500 Euro
  }
  if (!/^\d+(\.\d{0,2})?$/.test(s)) return NaN;
  return Math.round(parseFloat(s) * 100);
}

/** Cent -> "1.234,56" */
function fmt(cents) {
  const neg = cents < 0;
  cents = Math.abs(cents);
  const euro = Math.floor(cents / 100);
  const ct = cents % 100;
  return (neg ? '-' : '') + String(euro).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + String(ct).padStart(2, '0');
}

function dmy(iso) {
  const [y, m, d] = String(iso).split('-');
  return `${d}.${m}.${y}`;
}

function todayIso() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function nowStamp() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  return `${p(d.getDate())}.${p(d.getMonth() + 1)}.${d.getFullYear()}, ${p(d.getHours())}:${p(d.getMinutes())} Uhr`;
}

const EINER = ['null', 'eins', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun', 'zehn',
  'elf', 'zwölf', 'dreizehn', 'vierzehn', 'fünfzehn', 'sechzehn', 'siebzehn', 'achtzehn', 'neunzehn'];
const ZEHNER = ['', '', 'zwanzig', 'dreißig', 'vierzig', 'fünfzig', 'sechzig', 'siebzig', 'achtzig', 'neunzig'];

function unter100(n) {
  if (n < 20) return EINER[n];
  const e = n % 10, z = Math.floor(n / 10);
  return e === 0 ? ZEHNER[z] : (e === 1 ? 'ein' : EINER[e]) + 'und' + ZEHNER[z];
}
function unter1000(n) {
  const h = Math.floor(n / 100), r = n % 100;
  let s = '';
  if (h) s += (h === 1 ? 'ein' : EINER[h]) + 'hundert';
  if (r) s += unter100(r);
  return s;
}
/** Ganze Zahl -> deutsches Zahlwort (bis 999 Mio.) */
function zahlInWorten(n) {
  if (n === 0) return 'null';
  const mio = Math.floor(n / 1e6), tsd = Math.floor((n % 1e6) / 1000), rest = n % 1000;
  let s = '';
  if (mio) s += (mio === 1 ? 'eine Million' : unter1000(mio) + ' Millionen') + (tsd || rest ? ' ' : '');
  if (tsd) s += (tsd === 1 ? 'ein' : unter1000(tsd)) + 'tausend';
  if (rest) s += unter1000(rest);
  return s;
}
/** 12345 Cent -> "einhundertdreiundzwanzig Euro und fünfundvierzig Cent" */
function betragInWorten(cents) {
  const e = Math.floor(cents / 100), c = cents % 100;
  const ew = e === 1 ? 'ein' : zahlInWorten(e);
  let s = ew + ' Euro';
  if (c) s += ' und ' + (c === 1 ? 'ein' : zahlInWorten(c)) + ' Cent';
  return s;
}

/** Steuer aus Bruttobetrag herausrechnen */
function steuer(brutto, ust) {
  const satz = ust === '19' ? 19 : ust === '7' ? 7 : 0;
  if (!satz) return { satz: 0, netto: brutto, ust: 0 };
  const netto = Math.round(brutto * 100 / (100 + satz));
  return { satz, netto, ust: brutto - netto };
}

function slug(s) {
  return String(s || '')
    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue')
    .replace(/Ä/g, 'Ae').replace(/Ö/g, 'Oe').replace(/Ü/g, 'Ue').replace(/ß/g, 'ss')
    .normalize('NFKD').replace(/[^\w-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')
    .slice(0, 40);
}

function neueId() {
  return (crypto.randomUUID && crypto.randomUUID()) || String(Date.now()) + Math.random().toString(16).slice(2);
}

const $ = (sel, root = document) => root.querySelector(sel);

function toast(msg, ms = 2600) {
  const t = $('#toast');
  t.textContent = msg;
  t.hidden = false;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => { t.hidden = true; }, ms);
}

/* =========================================================
   Unterschriftenfeld
   ========================================================= */

class SignaturePad {
  constructor(canvas) {
    this.c = canvas;
    this.ctx = canvas.getContext('2d');
    this.strokes = [];
    this.cur = null;
    this.onchange = null;

    canvas.addEventListener('pointerdown', (e) => this.down(e));
    canvas.addEventListener('pointermove', (e) => this.move(e));
    canvas.addEventListener('pointerup', (e) => this.up(e));
    canvas.addEventListener('pointercancel', (e) => this.up(e));
    // iOS: Scrollen beim Unterschreiben sicher verhindern
    canvas.addEventListener('touchstart', (e) => e.preventDefault(), { passive: false });
    canvas.addEventListener('touchmove', (e) => e.preventDefault(), { passive: false });

    if ('ResizeObserver' in window) new ResizeObserver(() => this.resize()).observe(canvas);
    else window.addEventListener('resize', () => this.resize());
    this.resize();
  }

  get isEmpty() { return this.strokes.length === 0; }

  resize() {
    const r = this.c.getBoundingClientRect();
    if (!r.width || !r.height) return;
    const dpr = window.devicePixelRatio || 1;
    this.c.width = Math.round(r.width * dpr);
    this.c.height = Math.round(r.height * dpr);
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    this.redraw();
  }

  pos(e) {
    const r = this.c.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
  }

  down(e) {
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    e.preventDefault();
    try { this.c.setPointerCapture(e.pointerId); } catch (_) { /* egal */ }
    this.cur = [this.pos(e)];
    this.strokes.push(this.cur);
    this.drawDot(this.cur[0]);
  }

  move(e) {
    if (!this.cur) return;
    e.preventDefault();
    const evs = typeof e.getCoalescedEvents === 'function' ? e.getCoalescedEvents() : [e];
    for (const ev of (evs.length ? evs : [e])) {
      const p = this.pos(ev);
      const last = this.cur[this.cur.length - 1];
      if (Math.abs(p.x - last.x) + Math.abs(p.y - last.y) < 0.8) continue;
      this.cur.push(p);
      this.drawSeg(this.ctx, last, p);
    }
  }

  up() {
    if (!this.cur) return;
    this.cur = null;
    if (this.onchange) this.onchange();
  }

  style(ctx) {
    ctx.strokeStyle = '#0b1f4d';
    ctx.fillStyle = '#0b1f4d';
    ctx.lineWidth = 2.6;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
  }

  drawDot(p) {
    this.style(this.ctx);
    this.ctx.beginPath();
    this.ctx.arc(p.x, p.y, 1.3, 0, Math.PI * 2);
    this.ctx.fill();
  }

  drawSeg(ctx, a, b) {
    this.style(ctx);
    ctx.beginPath();
    ctx.moveTo(a.x, a.y);
    ctx.lineTo(b.x, b.y);
    ctx.stroke();
  }

  drawStrokes(ctx) {
    this.style(ctx);
    for (const s of this.strokes) {
      ctx.beginPath();
      if (s.length === 1) {
        ctx.arc(s[0].x, s[0].y, 1.3, 0, Math.PI * 2);
        ctx.fill();
        continue;
      }
      ctx.moveTo(s[0].x, s[0].y);
      for (let i = 1; i < s.length - 1; i++) {
        const mx = (s[i].x + s[i + 1].x) / 2, my = (s[i].y + s[i + 1].y) / 2;
        ctx.quadraticCurveTo(s[i].x, s[i].y, mx, my);
      }
      const l = s[s.length - 1];
      ctx.lineTo(l.x, l.y);
      ctx.stroke();
    }
  }

  redraw() {
    const ctx = this.ctx;
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, this.c.width, this.c.height);
    ctx.restore();
    this.drawStrokes(ctx);
  }

  clear() {
    this.strokes = [];
    this.cur = null;
    this.redraw();
    if (this.onchange) this.onchange();
  }

  /** Zugeschnittene Unterschrift als JPEG (weißer Hintergrund) */
  export() {
    if (this.isEmpty) return null;
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    for (const s of this.strokes) for (const p of s) {
      minX = Math.min(minX, p.x); minY = Math.min(minY, p.y);
      maxX = Math.max(maxX, p.x); maxY = Math.max(maxY, p.y);
    }
    const pad = 8;
    minX -= pad; minY -= pad; maxX += pad; maxY += pad;
    const w = Math.max(maxX - minX, 20), h = Math.max(maxY - minY, 20);
    const scale = 3;
    const off = document.createElement('canvas');
    off.width = Math.round(w * scale);
    off.height = Math.round(h * scale);
    const ctx = off.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, off.width, off.height);
    ctx.setTransform(scale, 0, 0, scale, -minX * scale, -minY * scale);
    this.drawStrokes(ctx);
    return { dataUrl: off.toDataURL('image/jpeg', 0.9), w, h };
  }
}

const MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

/** ISO-Zeitpunkt -> "17.11.2026, 10:05 Uhr" (Ortszeit) */
function stamp(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  const p = (n) => String(n).padStart(2, '0');
  return `${p(d.getDate())}.${p(d.getMonth() + 1)}.${d.getFullYear()}, ${p(d.getHours())}:${p(d.getMinutes())} Uhr`;
}

function el(tag, cls, text) {
  const e = document.createElement(tag);
  if (cls) e.className = cls;
  if (text !== undefined && text !== null) e.textContent = text;
  return e;
}

function badge(text, cls) {
  return el('span', 'badge ' + (cls || ''), text);
}

function initials(name) {
  return String(name || '?').replace(/^DJ\s+/i, '').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') || '?';
}

function oneLine(s) {
  return String(s || '').split(/\r?\n/).map((x) => x.trim()).filter(Boolean).join(', ');
}

/** Eigene Rückfrage statt confirm() */
function ask(text, okLabel = 'OK') {
  const dlg = $('#dlgConfirm');
  $('#confirmText').textContent = text;
  $('#confirmOk').textContent = okLabel;
  return new Promise((resolve) => {
    const ok = () => { cleanup(); dlg.close(); resolve(true); };
    const cancel = () => { cleanup(); resolve(false); };
    function cleanup() {
      $('#confirmOk').removeEventListener('click', ok);
      dlg.removeEventListener('close', cancel);
    }
    $('#confirmOk').addEventListener('click', ok);
    dlg.addEventListener('close', cancel);
    dlg.showModal();
  });
}

/* =========================================================
   Server: Anfragen und Abgleich
   ========================================================= */

class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.status = status;
  }
}

function xsrfToken() {
  const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : '';
}

/** Anfrage an die Schnittstelle unter api/… (relativ zur App-Adresse) */
async function api(method, path, body) {
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  const token = xsrfToken();
  if (token) headers['X-XSRF-TOKEN'] = token;
  let payload;
  if (body instanceof FormData) payload = body;
  else if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let res;
  try {
    res = await fetch(path, { method, headers, body: payload, credentials: 'same-origin', cache: 'no-store' });
  } catch (e) {
    setSync('offline');
    throw new ApiError('Keine Verbindung zum Server. Bitte Internet prüfen und noch einmal versuchen – die Eingaben bleiben erhalten.', 0);
  }

  if (res.status === 401) {
    setSync('login');
    throw new ApiError('Deine Anmeldung ist abgelaufen. Bitte neu anmelden.', 401);
  }
  if (res.status === 419) throw new ApiError('Die Sitzung ist abgelaufen. Bitte die Seite neu laden.', 419);
  if (res.status === 204) return null;

  let data = null;
  try { data = await res.json(); } catch (e) { data = null; }
  if (!res.ok) {
    let msg = (data && data.message) || `Fehler ${res.status} beim Speichern.`;
    if (data && data.errors) {
      const first = Object.values(data.errors)[0];
      if (first && first[0]) msg = first[0];
    }
    if (res.status === 409) sync(true).catch(() => {});
    throw new ApiError(msg, res.status);
  }
  return data;
}

/** Datei (PDF, Unterschrift, Rider) vom Server holen */
async function fetchBlob(url) {
  let res;
  try {
    res = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
  } catch (e) {
    setSync('offline');
    throw new ApiError('Keine Verbindung zum Server.', 0);
  }
  if (res.status === 401 || res.redirected) {
    setSync('login');
    throw new ApiError('Deine Anmeldung ist abgelaufen. Bitte neu anmelden.', 401);
  }
  if (!res.ok) throw new ApiError('Die Datei wurde nicht gefunden.', res.status);
  return res.blob();
}

function blobToDataUrl(blob) {
  return new Promise((resolve, reject) => {
    const r = new FileReader();
    r.onload = () => resolve(r.result);
    r.onerror = () => reject(r.error);
    r.readAsDataURL(blob);
  });
}

/** Gemeinsamer Stand aller Geräte */
const S = { version: null, companies: [], contacts: [], employees: [], receipts: [], user: null, ready: false };
let syncing = null;

function setSync(state) {
  const elState = $('#syncState');
  elState.dataset.state = state;
  elState.textContent = { ok: 'synchron', offline: 'offline', login: 'nicht angemeldet', loading: 'lädt …' }[state] || state;
  $('#loginBox').hidden = state !== 'login';
}

/** Stand vom Server holen – mit bekannter Version kommt nur „unverändert“ zurück */
function sync(force = false) {
  if (syncing) return syncing;
  syncing = (async () => {
    try {
      const query = !force && S.version ? '?version=' + encodeURIComponent(S.version) : '';
      const d = await api('GET', 'api/stand' + query);
      const first = !S.ready;
      if (!d.unchanged) {
        S.companies = d.companies;
        S.contacts = d.contacts;
        S.employees = d.employees;
        S.receipts = d.receipts;
      }
      S.user = d.user;
      S.version = d.version;
      S.ready = true;
      setSync('ok');
      if (!d.unchanged || first) onDataChanged(first);
    } finally {
      syncing = null;
    }
  })();
  return syncing;
}

function upsert(list, item) {
  const i = list.findIndex((x) => x.id === item.id);
  if (i >= 0) list[i] = item;
  else list.unshift(item);
  S.version = null; // nächster Abgleich lädt alles neu
}

function sortByName(list) {
  list.sort((a, b) => a.name.localeCompare(b.name, 'de'));
}

/* =========================================================
   Firmen und Nummernkreis
   ========================================================= */

function activeCompanyId() {
  let id = null;
  try { id = Number(localStorage.getItem('quittung.aktiveFirma')) || null; } catch (e) { id = null; }
  if (!S.companies.some((c) => c.id === id)) id = S.companies[0] ? S.companies[0].id : null;
  return id;
}

function setActiveCompany(id) {
  try { localStorage.setItem('quittung.aktiveFirma', String(id)); } catch (e) { /* egal */ }
}

function activeCompany() {
  const id = activeCompanyId();
  return S.companies.find((c) => c.id === id) || null;
}

function formatNr(prefix, year, n) {
  return `${(prefix || 'Q').trim()}-${year}-${String(n).padStart(4, '0')}`;
}

/** Voraussichtliche nächste Nummer – verbindlich vergibt sie der Server beim Speichern */
function nextSequence(c, year) {
  let n = (Number(c.counters && c.counters[year]) || 0) + 1;
  const used = new Set(S.receipts.map((r) => r.number));
  while (used.has(formatNr(c.prefix, year, n))) n++;
  return n;
}

/* =========================================================
   PDF-Erzeugung
   ========================================================= */

const ART_TEXT = { einnahme: 'Einnahme', ausgabe: 'Ausgabe', vorschuss: 'Vorschuss' };

/** Quittung vom Server -> Daten für das PDF */
function pdfData(r, signatureDataUrl) {
  const lastEdit = (r.changes || []).find((c) => c.action === 'updated');
  return {
    nr: r.number,
    art: r.type,
    datum: r.date,
    betrag: r.amount_cents,
    ust: r.tax,
    partnerName: r.partner_name,
    partnerAdresse: r.partner_address || '',
    zweck: r.purpose,
    zahlungsart: r.payment_method,
    ort: r.place,
    unterzeichner: r.signer_name || '',
    firma: {
      firma: r.company.name, strasse: r.company.street, plzOrt: r.company.zip_city,
      steuernr: r.company.tax_number, ustid: r.company.vat_id, kontakt: r.company.contact
    },
    unterschrift: signatureDataUrl
      ? { dataUrl: signatureDataUrl, w: (r.signature && r.signature.width) || 200, h: (r.signature && r.signature.height) || 80 }
      : null,
    erstelltText: stamp(r.created_at),
    geaendertText: lastEdit ? `${stamp(lastEdit.at)} (${lastEdit.user})` : '',
    storno: r.cancelled_at ? { text: stamp(r.cancelled_at), grund: r.cancel_reason || '' } : null
  };
}

function buildPdf(q) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
  const F = q.firma;           // Firmendaten (Momentaufnahme beim Erstellen)
  const st = steuer(q.betrag, q.ust);
  const L = 20, R = 190, W = R - L;
  const artText = ART_TEXT[q.art] || 'Ausgabe';

  doc.setProperties({
    title: `Quittung ${q.nr}`,
    subject: `${F.firma}: ${artText} ${fmt(q.betrag)} EUR, ${dmy(q.datum)}: ${q.zweck}`,
    author: F.firma,
    keywords: `Quittung, Barbeleg, Kassenbuch, ${artText}, ${q.nr}, ${F.firma}`,
    creator: `Quittungsprogramm ${APP_VERSION}`
  });

  const gray = () => doc.setTextColor(95, 105, 98);
  const black = () => doc.setTextColor(15, 20, 17);

  // ---------- Kopf ----------
  let y = 24;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(10.5);
  doc.setTextColor(20, 83, 45);
  doc.text(doc.splitTextToSize(F.firma, 105)[0], L, 13);

  doc.setFontSize(26);
  black();
  doc.text('QUITTUNG', L, y);

  doc.setFontSize(11);
  doc.text(`Nr. ${q.nr}`, R, y - 7, { align: 'right' });
  doc.setFont('helvetica', 'normal');
  doc.text(`Datum: ${dmy(q.datum)}`, R, y - 1.5, { align: 'right' });

  y += 7;
  doc.setFontSize(11);
  gray();
  doc.text(`${q.art === 'vorschuss' ? 'Vorschuss an Mitarbeiter' : artText} – Zahlungsart: ${q.zahlungsart}`, L, y);

  y += 4;
  doc.setDrawColor(20, 83, 45);
  doc.setLineWidth(0.8);
  doc.line(L, y, R, y);

  // ---------- Betragsbox ----------
  y += 8;
  const worte = 'in Worten: ' + betragInWorten(q.betrag);
  doc.setFontSize(10);
  const wLines = doc.splitTextToSize(worte, W - 10);
  const boxH = 20 + wLines.length * 4.6;
  doc.setDrawColor(60, 70, 64);
  doc.setLineWidth(0.35);
  doc.setFillColor(244, 248, 245);
  doc.roundedRect(L, y, W, boxH, 2, 2, 'FD');
  doc.setFontSize(10);
  gray();
  doc.text('Betrag' + (st.satz ? ` (brutto, inkl. ${st.satz} % USt)` : ''), L + 5, y + 7);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(22);
  black();
  doc.text(`${fmt(q.betrag)} EUR`, R - 5, y + 11, { align: 'right' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  gray();
  doc.text(wLines, L + 5, y + 18.5);
  y += boxH + 10;

  // ---------- Angaben ----------
  const firmaZeilen = [F.firma, F.strasse, F.plzOrt].filter(Boolean);
  const partnerZeilen = [q.partnerName, ...String(q.partnerAdresse || '').split(/\r?\n/)].map((s) => s.trim()).filter(Boolean);
  const zahler = q.art === 'einnahme' ? partnerZeilen : firmaZeilen;
  const empfaenger = q.art === 'einnahme' ? firmaZeilen : partnerZeilen;

  function row(label, lines, bold) {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9.5);
    gray();
    doc.text(label, L, y);
    doc.setFontSize(11);
    black();
    const wrapped = [];
    lines.forEach((l, i) => {
      doc.setFont('helvetica', bold && i === 0 ? 'bold' : 'normal');
      doc.splitTextToSize(l, W - 52).forEach((t) => wrapped.push({ t, b: bold && i === 0 }));
    });
    let yy = y;
    for (const w of wrapped) {
      doc.setFont('helvetica', w.b ? 'bold' : 'normal');
      doc.text(w.t, L + 52, yy);
      yy += 5;
    }
    y = Math.max(y + 5, yy) + 3.5;
  }

  row('Gezahlt von:', zahler, true);
  row(q.art === 'vorschuss' ? 'Empfänger (Mitarbeiter):' : 'Zahlungsempfänger:', empfaenger, true);
  row('Verwendungszweck:', [q.zweck]);

  // ---------- Steuer ----------
  y += 1;
  doc.setDrawColor(215, 221, 216);
  doc.setLineWidth(0.3);
  doc.line(L, y - 3, R, y - 3);
  doc.setFontSize(10.5);
  if (st.satz) {
    const xr = R;
    const xl = L + 52;
    const line = (a, b, bold) => {
      doc.setFont('helvetica', bold ? 'bold' : 'normal');
      black();
      doc.text(a, xl, y + 2);
      doc.text(b, xr, y + 2, { align: 'right' });
      y += 5.5;
    };
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9.5);
    gray();
    doc.text('Steuer:', L, y + 2);
    doc.setFontSize(10.5);
    line('Nettobetrag', `${fmt(st.netto)} EUR`);
    line(`zzgl. ${st.satz} % Umsatzsteuer`, `${fmt(st.ust)} EUR`);
    line('Gesamtbetrag', `${fmt(q.betrag)} EUR`, true);
  } else {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9.5);
    gray();
    doc.text('Steuer:', L, y + 2);
    doc.setFontSize(10.5);
    black();
    const txt = q.ust === 'ku'
      ? 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).'
      : q.art === 'vorschuss'
        ? 'Lohnvorschuss – keine Umsatzsteuer.'
        : 'Im Betrag ist keine Umsatzsteuer enthalten.';
    const tl = doc.splitTextToSize(txt, W - 52);
    doc.text(tl, L + 52, y + 2);
    y += tl.length * 5 + 0.5;
  }

  // ---------- Bestätigung ----------
  y += 8;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11.5);
  black();
  const best = q.zahlungsart === 'Bar'
    ? `Den Betrag von ${fmt(q.betrag)} EUR in bar dankend erhalten.`
    : `Den Betrag von ${fmt(q.betrag)} EUR (${q.zahlungsart}) dankend erhalten.`;
  doc.text(doc.splitTextToSize(best, W), L, y);

  // ---------- Unterschrift ----------
  let lineY = Math.max(y + 38, 200);
  if (lineY > 255) { doc.addPage(); lineY = 70; }

  // Ort, Datum
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(11);
  black();
  doc.text(`${q.ort}, ${dmy(q.datum)}`, L, lineY - 2);
  doc.setDrawColor(60, 70, 64);
  doc.setLineWidth(0.3);
  doc.line(L, lineY, L + 65, lineY);
  doc.setFontSize(9);
  gray();
  doc.text('Ort, Datum', L, lineY + 4.5);

  // Unterschrift-Bild
  const sx = R - 85, sw = 85, maxH = 30;
  if (q.unterschrift) {
    const ratio = q.unterschrift.w / q.unterschrift.h;
    let w = sw, h = w / ratio;
    if (h > maxH) { h = maxH; w = h * ratio; }
    const format = String(q.unterschrift.dataUrl).startsWith('data:image/png') ? 'PNG' : 'JPEG';
    doc.addImage(q.unterschrift.dataUrl, format, sx + (sw - w) / 2, lineY - h - 0.5, w, h, undefined, 'FAST');
  }
  doc.line(sx, lineY, R, lineY);
  doc.text(q.art === 'vorschuss' ? 'Unterschrift Mitarbeiter' : 'Unterschrift Zahlungsempfänger', sx, lineY + 4.5);
  if (q.unterzeichner) {
    doc.setFontSize(10);
    black();
    doc.text(`(${q.unterzeichner})`, sx, lineY + 9.5);
  }

  // ---------- Fußzeile ----------
  const fy = 280;
  doc.setDrawColor(215, 221, 216);
  doc.line(L, fy - 6, R, fy - 6);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  gray();
  const f1 = [F.firma, F.strasse, F.plzOrt].filter(Boolean).join(' · ');
  const f2 = [F.steuernr && `Steuernummer: ${F.steuernr}`, F.ustid && `USt-IdNr.: ${F.ustid}`, F.kontakt].filter(Boolean).join(' · ');
  doc.text(doc.splitTextToSize(f1, 120), L, fy - 1.5);
  if (f2) doc.text(doc.splitTextToSize(f2, 120), L, fy + 2.5);
  doc.text(`Beleg erstellt: ${q.erstelltText}`, R, fy - 1.5, { align: 'right' });
  doc.text(`Beleg-Nr. ${q.nr}`, R, fy + 2.5, { align: 'right' });
  if (q.geaendertText) doc.text(`Geändert: ${q.geaendertText}`, R, fy + 6.5, { align: 'right' });

  // ---------- Storno-Vermerk ----------
  if (q.storno) {
    doc.setPage(1);
    doc.setTextColor(180, 35, 24);
    try { doc.setGState(new doc.GState({ opacity: 0.28 })); } catch (e) { /* ältere jsPDF-Version */ }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(72);
    doc.text('STORNIERT', 105, 175, { align: 'center', angle: 28 });
    try { doc.setGState(new doc.GState({ opacity: 1 })); } catch (e) { /* egal */ }
    doc.setFontSize(10);
    doc.text(doc.splitTextToSize(`Storniert am ${q.storno.text}${q.storno.grund ? ' – Grund: ' + q.storno.grund : ''}`, W), L, fy - 10);
  }

  return doc.output('arraybuffer');
}

function pdfFileName(q) {
  return `Quittung_${slug(q.nr)}_${q.datum}_${slug(q.partnerName) || 'Beleg'}${q.storno ? '_STORNIERT' : ''}.pdf`;
}

/* =========================================================
   PDF laden, teilen, speichern, öffnen
   ========================================================= */

const pdfCache = new Map();
const pdfKey = (r) => `${r.id}|${r.updated_at}|${r.pdf_url ? 1 : 0}`;

async function uploadPdf(r, buf) {
  const fd = new FormData();
  fd.append('pdf', new Blob([buf], { type: 'application/pdf' }), pdfFileName(pdfData(r, null)));
  const res = await api('POST', `api/quittungen/${r.id}/pdf`, fd);
  upsert(S.receipts, res.data);
  pdfCache.set(pdfKey(res.data), buf);
  return res.data;
}

async function signatureFor(r) {
  return r.signature ? blobToDataUrl(await fetchBlob(r.signature.url)) : null;
}

/** PDF einer Quittung: gespeichertes PDF, bei Storno mit Vermerk neu erzeugt */
async function pdfFor(r) {
  const key = pdfKey(r);
  if (pdfCache.has(key)) return pdfCache.get(key);
  let buf;
  if (r.pdf_url && !r.cancelled_at) {
    buf = await (await fetchBlob(r.pdf_url)).arrayBuffer();
  } else {
    buf = buildPdf(pdfData(r, await signatureFor(r)));
    if (!r.pdf_url && !r.cancelled_at) {
      try { await uploadPdf(r, buf); } catch (e) { /* nächstes Mal erneut */ }
    }
  }
  pdfCache.set(key, buf);
  return buf;
}

function pdfDoc(r, buf) {
  const d = pdfData(r, null);
  return { pdf: buf, dateiname: pdfFileName(d), nr: r.number, firma: { firma: r.company.name }, datum: r.date, betrag: r.amount_cents };
}

function pdfBlob(q) {
  return new Blob([q.pdf], { type: 'application/pdf' });
}

function downloadPdf(q) {
  const url = URL.createObjectURL(pdfBlob(q));
  const a = document.createElement('a');
  a.href = url;
  a.download = q.dateiname;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 60000);
}

/** Fenster sofort beim Tippen öffnen (sonst blockt der Browser es), Inhalt danach laden */
function openBlobLater(load, fallback) {
  const w = window.open('', '_blank');
  load().then((blob) => {
    const url = URL.createObjectURL(blob);
    if (w && !w.closed) w.location.href = url;
    else fallback(blob);
    setTimeout(() => URL.revokeObjectURL(url), 120000);
  }).catch((e) => {
    if (w) w.close();
    toast(e.message || 'Öffnen nicht möglich.');
  });
}

async function sharePdf(q) {
  const file = new File([q.pdf], q.dateiname, { type: 'application/pdf' });
  if (navigator.canShare && navigator.canShare({ files: [file] })) {
    try {
      await navigator.share({
        files: [file],
        title: `Quittung ${q.nr}`,
        text: `Quittung ${q.nr} (${q.firma.firma}) vom ${dmy(q.datum)} über ${fmt(q.betrag)} EUR`
      });
    } catch (e) {
      if (e && e.name === 'NotAllowedError') toast('Bitte noch einmal auf „Teilen“ tippen.');
      else if (e && e.name !== 'AbortError') { toast('Teilen nicht möglich – PDF wird gespeichert.'); downloadPdf(q); }
    }
  } else {
    toast('Teilen wird hier nicht unterstützt – PDF wird gespeichert.');
    downloadPdf(q);
  }
}

function receiptPdfAction(r, action) {
  if (action === 'open') {
    openBlobLater(async () => new Blob([await pdfFor(r)], { type: 'application/pdf' }), (blob) => downloadPdf({ pdf: blob, dateiname: pdfDoc(r, null).dateiname }));
    return;
  }
  pdfFor(r)
    .then((buf) => (action === 'share' ? sharePdf(pdfDoc(r, buf)) : downloadPdf(pdfDoc(r, buf))))
    .catch((e) => toast(e.message || 'PDF nicht verfügbar.'));
}

/* =========================================================
   Auswahlliste mit Suche (Kontakte, Mitarbeiter)
   ========================================================= */

function attachSuggest(input, box, { items, onPick }) {
  let hi = -1;
  let current = [];

  function render() {
    current = items(input.value.trim().toLowerCase(), input.value.trim());
    box.innerHTML = '';
    if (!current.length) {
      box.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      return;
    }
    current.forEach((it, i) => {
      const b = el('button');
      b.type = 'button';
      b.setAttribute('role', 'option');
      if (i === hi) b.classList.add('hi');
      if (it.add) {
        b.appendChild(el('span', 'add', it.label));
      } else {
        const title = el('b', null, it.label);
        if (it.badge) title.append(' ', badge(it.badge.text, it.badge.cls));
        b.append(title, el('small', null, it.sub || ''));
      }
      b.addEventListener('mousedown', (e) => e.preventDefault());
      b.addEventListener('click', () => pick(it));
      box.appendChild(b);
    });
    box.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }

  function pick(it) {
    box.hidden = true;
    hi = -1;
    input.setAttribute('aria-expanded', 'false');
    onPick(it);
  }

  input.addEventListener('focus', () => { hi = -1; render(); });
  input.addEventListener('input', () => { hi = -1; render(); });
  input.addEventListener('blur', () => setTimeout(() => { box.hidden = true; input.setAttribute('aria-expanded', 'false'); }, 150));
  input.addEventListener('keydown', (e) => {
    if (box.hidden || !current.length) return;
    if (e.key === 'ArrowDown') { hi = Math.min(hi + 1, current.length - 1); render(); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { hi = Math.max(hi - 1, 0); render(); e.preventDefault(); }
    else if (e.key === 'Enter' && hi >= 0) { e.preventDefault(); pick(current[hi]); }
    else if (e.key === 'Escape') { box.hidden = true; }
  });
}

function lastUsedText(field, id) {
  const r = S.receipts.find((x) => x[field] === id);
  return r ? 'zuletzt ' + dmy(r.date).slice(0, 6) : '';
}

function advanceSum(employeeId, yearMonth) {
  return S.receipts
    .filter((r) => r.type === 'vorschuss' && r.employee_id === employeeId && !r.cancelled_at && r.date.startsWith(yearMonth))
    .reduce((sum, r) => sum + r.amount_cents, 0);
}

function monthName(yearMonth) {
  const [y, m] = yearMonth.split('-');
  return `${MONATE[Number(m) - 1]} ${y}`;
}

/* =========================================================
   Oberfläche: Neue Quittung
   ========================================================= */

const formQ = $('#formQ');
const formS = $('#formS');
const pad = new SignaturePad($('#sigCanvas'));
let lastCreated = null;
let pickedContactId = null;
let pickedEmployeeId = null;
let correctsReceipt = null;
let autoZweck = '';

function currentView() {
  const v = document.querySelector('.view.active');
  return v ? v.id.replace('view-', '') : 'neu';
}

function showView(name) {
  document.querySelectorAll('.view').forEach((v) => v.classList.toggle('active', v.id === 'view-' + name));
  document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t.dataset.view === name));
  if (name === 'verlauf') drawList();
  if (name === 'stammdaten') renderStammdaten();
  if (name === 'neu') requestAnimationFrame(() => pad.resize());
  window.scrollTo(0, 0);
}
document.querySelectorAll('.tab').forEach((t) => t.addEventListener('click', () => showView(t.dataset.view)));

function currentArt() {
  return formQ.elements.art.value;
}

function formMonth() {
  return ($('#fDatum').value || todayIso()).slice(0, 7);
}

function updateBrand() {
  const c = activeCompany();
  $('#brandFirma').textContent = c ? c.name : 'Noch keine Firma angelegt';
}

/** Auswahlliste „Quittung für Firma“ – nur sichtbar, wenn es mehr als eine Firma gibt */
function renderFirmaSelect() {
  const sel = $('#fFirma');
  sel.innerHTML = '';
  for (const c of S.companies) {
    const o = el('option', null, c.name);
    o.value = c.id;
    sel.appendChild(o);
  }
  sel.value = activeCompanyId() || '';
  $('#firmaWahl').hidden = S.companies.length < 2;
}

function setNummer() {
  const c = activeCompany();
  const year = Number(($('#fDatum').value || todayIso()).slice(0, 4));
  $('#fNr').value = c ? formatNr(c.prefix, year, nextSequence(c, year)) : '';
}

function vorschussZweck() {
  return `Vorschuss auf Lohn ${monthName(formMonth())}`;
}

/** Felder passend zu Einnahme/Ausgabe/Vorschuss und gewählter Firma beschriften */
function applyArt(setDefaults) {
  const c = activeCompany();
  const art = currentArt();
  const isAdvance = art === 'vorschuss';
  const firmaTxt = c ? [c.name, c.street, c.zip_city].filter(Boolean).join(', ') : '– bitte unter „Stammdaten“ anlegen –';

  $('#partnerCard').hidden = isAdvance;
  $('#employeeCard').hidden = !isAdvance;

  if (art === 'einnahme') {
    $('#hPartner').textContent = 'Gezahlt von (Kunde / Zahler)';
    $('#fixedParty').innerHTML = '<b>Empfänger (wir)</b>';
    $('#hSig').textContent = 'Unterschrift Empfänger (für die Firma)';
    $('#sigHelp').textContent = 'Wer das Geld für die Firma angenommen hat, unterschreibt hier mit dem Finger.';
    $('#lblSigner').textContent = 'Name des Unterzeichners (Mitarbeiter)';
  } else if (art === 'ausgabe') {
    $('#hPartner').textContent = 'Zahlungsempfänger (bekommt das Geld)';
    $('#fixedParty').innerHTML = '<b>Gezahlt von (wir)</b>';
    $('#hSig').textContent = 'Unterschrift Zahlungsempfänger';
    $('#sigHelp').textContent = 'Die Person, die das Geld bekommt, unterschreibt hier mit dem Finger.';
    $('#lblSigner').textContent = 'Name in Druckbuchstaben';
  } else {
    $('#hSig').textContent = 'Unterschrift Mitarbeiter';
    $('#sigHelp').textContent = 'Der Mitarbeiter, der den Vorschuss bekommt, unterschreibt hier mit dem Finger.';
    $('#lblSigner').textContent = 'Name in Druckbuchstaben';
  }
  if (!isAdvance) $('#fixedParty').append(document.createTextNode(firmaTxt));

  $('#fUst').disabled = isAdvance;
  if (isAdvance) $('#fUst').value = '0';
  else if (setDefaults) {
    $('#fUst').value = art === 'einnahme' ? (c && c.is_small_business ? 'ku' : ((c && c.default_tax) || '19')) : '0';
  }

  const zweck = $('#fZweck');
  if (isAdvance && (!zweck.value.trim() || zweck.value === autoZweck)) {
    autoZweck = vorschussZweck();
    zweck.value = autoZweck;
  } else if (!isAdvance && autoZweck && zweck.value === autoZweck) {
    zweck.value = '';
    autoZweck = '';
  }

  updateCalc();
  updateEmployeeInfo();
}

function updateCalc() {
  const cents = parseEuro($('#fBetrag').value);
  const box = $('#calcBox');
  const c = activeCompany();
  if (!(cents > 0)) { box.hidden = true; } else {
    const st = steuer(cents, $('#fUst').value);
    box.innerHTML = '';
    const add = (a, b) => {
      const r = el('div', 'row');
      r.append(el('span', null, a), el('b', null, b));
      box.appendChild(r);
    };
    if (st.satz) {
      add('Netto', fmt(st.netto) + ' €');
      add(`USt ${st.satz} %`, fmt(st.ust) + ' €');
    }
    add('Gesamt', fmt(cents) + ' €');
    box.appendChild(el('div', 'words', betragInWorten(cents)));
    box.hidden = false;
  }

  // Hinweis bei Einnahmen über 250 € (Kleinbetragsrechnung überschritten)
  const hint = $('#hint250');
  const fehlt = [];
  if (currentArt() === 'einnahme' && cents > 25000) {
    if (c && !c.tax_number && !c.vat_id) fehlt.push(`Steuernummer oder USt-IdNr. von ${c.name} (unter „Stammdaten“)`);
    if (!$('#fPartnerAdresse').value.trim()) fehlt.push('Anschrift des Kunden');
  }
  if (fehlt.length) {
    hint.textContent = 'Über 250 € gelten die vollen Rechnungsangaben. Es fehlt: ' + fehlt.join(', ') + '.';
    hint.hidden = false;
  } else hint.hidden = true;
}

function updatePartnerInfo() {
  const info = $('#partnerInfo');
  const name = $('#fPartnerName').value.trim();
  const contact = S.contacts.find((c) => c.id === pickedContactId);
  info.classList.remove('neu');
  if (contact) {
    info.textContent = `Gespeicherter ${contact.is_dj ? 'DJ' : 'Kontakt'}: ${contact.name}`;
    info.hidden = false;
  } else if (name) {
    info.textContent = $('#fEinmalig').checked
      ? 'Wird nicht als Kontakt gespeichert.'
      : 'Neuer Kontakt – wird beim Erstellen gespeichert.';
    info.classList.add('neu');
    info.hidden = false;
  } else {
    info.hidden = true;
  }
}

function updateEmployeeInfo() {
  const info = $('#employeeInfo');
  const e = S.employees.find((x) => x.id === pickedEmployeeId);
  $('#employeeEmpty').hidden = S.employees.length > 0;
  if (!e) { info.hidden = true; return; }
  const month = formMonth();
  const sum = advanceSum(e.id, month);
  info.textContent = sum
    ? `${e.name}: im ${monthName(month)} bisher ${fmt(sum)} € Vorschuss`
    : `${e.name}: im ${monthName(month)} noch kein Vorschuss`;
  info.hidden = false;
}

attachSuggest($('#fPartnerName'), $('#partnerSuggest'), {
  items(term, raw) {
    const list = S.contacts
      .filter((c) => !term || [c.name, c.real_name, c.address].join(' ').toLowerCase().includes(term))
      .sort((a, b) => {
        const sa = a.name.toLowerCase().startsWith(term) ? 0 : 1;
        const sb = b.name.toLowerCase().startsWith(term) ? 0 : 1;
        return sa - sb || a.name.localeCompare(b.name, 'de');
      })
      .slice(0, 30)
      .map((c) => ({
        label: c.name,
        sub: [oneLine(c.address), lastUsedText('contact_id', c.id)].filter(Boolean).join(' · '),
        badge: c.is_dj ? { text: 'DJ', cls: 'dj' } : null,
        contact: c
      }));
    if (raw && !S.contacts.some((c) => c.name.toLowerCase() === term)) {
      list.push({ add: true, label: `„${raw}“ als neuen Namen verwenden` });
    }
    return list;
  },
  onPick(it) {
    if (it.contact) {
      $('#fPartnerName').value = it.contact.name;
      $('#fPartnerAdresse').value = it.contact.address || '';
      pickedContactId = it.contact.id;
    }
    updatePartnerInfo();
    updateCalc();
  }
});

$('#fPartnerName').addEventListener('input', () => {
  const contact = S.contacts.find((c) => c.id === pickedContactId);
  if (contact && contact.name !== $('#fPartnerName').value.trim()) pickedContactId = null;
  updatePartnerInfo();
});
$('#fEinmalig').addEventListener('change', updatePartnerInfo);

attachSuggest($('#fEmployee'), $('#employeeSuggest'), {
  items(term, raw) {
    const month = formMonth();
    const list = S.employees
      .filter((e) => !term || [e.name, e.note].join(' ').toLowerCase().includes(term))
      .slice(0, 30)
      .map((e) => {
        const sum = advanceSum(e.id, month);
        return { label: e.name, sub: [e.note, sum ? `im ${MONATE[Number(month.slice(5)) - 1]} schon ${fmt(sum)} €` : ''].filter(Boolean).join(' · '), employee: e };
      });
    if (raw && !S.employees.some((e) => e.name.toLowerCase() === term)) {
      list.push({ add: true, label: `+ „${raw}“ als Mitarbeiter anlegen`, name: raw });
    }
    return list;
  },
  async onPick(it) {
    let e = it.employee;
    if (it.add) {
      try {
        e = (await api('POST', 'api/mitarbeiter', { name: it.name })).data;
        S.employees.push(e);
        sortByName(S.employees);
        toast(`${e.name} als Mitarbeiter angelegt.`);
      } catch (ex) {
        toast(ex.message, 4000);
        return;
      }
    }
    $('#fEmployee').value = e.name;
    $('#fEmployee').classList.remove('invalid');
    pickedEmployeeId = e.id;
    updateEmployeeInfo();
  }
});

$('#fEmployee').addEventListener('input', () => {
  const e = S.employees.find((x) => x.id === pickedEmployeeId);
  if (e && e.name !== $('#fEmployee').value.trim()) pickedEmployeeId = null;
  updateEmployeeInfo();
});

function resetForm() {
  formQ.reset();
  pickedContactId = null;
  pickedEmployeeId = null;
  correctsReceipt = null;
  autoZweck = '';
  $('#correctionBox').hidden = true;
  renderFirmaSelect();
  $('#fDatum').value = todayIso();
  const c = activeCompany();
  $('#fOrt').value = (c && c.default_place) || '';
  setNummer();
  pad.clear();
  formQ.querySelectorAll('.invalid').forEach((x) => x.classList.remove('invalid'));
  $('#formError').hidden = true;
  applyArt(true);
  updatePartnerInfo();
}

/** Stornierte Quittung als Vorlage für die korrigierte Neuausstellung */
function startCorrection(r) {
  $('#doneBox').hidden = true;
  formQ.hidden = false;
  resetForm();
  correctsReceipt = r;
  if (r.company_id && S.companies.some((c) => c.id === r.company_id)) {
    setActiveCompany(r.company_id);
    renderFirmaSelect();
    updateBrand();
  }
  formQ.elements.art.value = r.type;
  $('#fDatum').value = r.date;
  $('#fBetrag').value = fmt(r.amount_cents);
  if (r.type === 'vorschuss') {
    const e = S.employees.find((x) => x.id === r.employee_id);
    if (e) { pickedEmployeeId = e.id; $('#fEmployee').value = e.name; }
  } else {
    $('#fPartnerName').value = r.partner_name;
    $('#fPartnerAdresse').value = r.partner_address || '';
    pickedContactId = S.contacts.some((c) => c.id === r.contact_id) ? r.contact_id : null;
  }
  $('#fZweck').value = r.purpose;
  $('#fZahlungsart').value = r.payment_method;
  $('#fOrt').value = r.place;
  $('#fSigner').value = r.signer_name || '';
  applyArt(false);
  if (r.type !== 'vorschuss') $('#fUst').value = r.tax;
  setNummer();
  updateCalc();
  updatePartnerInfo();
  $('#correctionNr').textContent = r.number;
  $('#correctionBox').hidden = false;
  showView('neu');
}

function showError(box, msg) {
  box.textContent = msg;
  box.hidden = false;
  box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

formQ.addEventListener('change', (e) => {
  if (e.target.name === 'art') applyArt(true);
  if (e.target.name === 'ust') updateCalc();
  if (e.target.name === 'datum') {
    setNummer();
    if (currentArt() === 'vorschuss') applyArt(false);
  }
  if (e.target.name === 'firmaId') {
    const before = activeCompany();
    setActiveCompany(Number(e.target.value));
    const now = activeCompany();
    // Ort nur übernehmen, wenn er noch der Standard der vorherigen Firma war
    if (!$('#fOrt').value.trim() || (before && $('#fOrt').value.trim() === before.default_place)) $('#fOrt').value = now.default_place || '';
    updateBrand();
    setNummer();
    applyArt(true);
    toast(`Quittung für: ${now.name}`);
  }
});
$('#fBetrag').addEventListener('input', updateCalc);
$('#fPartnerAdresse').addEventListener('input', updateCalc);
$('#fBetrag').addEventListener('blur', () => {
  const c = parseEuro($('#fBetrag').value);
  if (c > 0) $('#fBetrag').value = fmt(c);
});
formQ.addEventListener('input', (e) => e.target.classList.remove('invalid'));
$('#sigClear').addEventListener('click', () => pad.clear());
pad.onchange = () => $('.sigwrap').classList.remove('invalid');
$('#btnReset').addEventListener('click', async () => {
  if (await ask('Alle Eingaben verwerfen?', 'Verwerfen')) resetForm();
});
$('#btnCorrectionCancel').addEventListener('click', () => {
  correctsReceipt = null;
  $('#correctionBox').hidden = true;
});

formQ.addEventListener('submit', async (e) => {
  e.preventDefault();
  const company = activeCompany();
  const err = $('#formError');
  err.hidden = true;
  formQ.querySelectorAll('.invalid').forEach((x) => x.classList.remove('invalid'));
  $('.sigwrap').classList.remove('invalid');

  if (!company) {
    showError(err, 'Bitte zuerst unter „Stammdaten“ eine Firma anlegen.');
    return;
  }

  const fe = formQ.elements;
  const art = currentArt();
  const isAdvance = art === 'vorschuss';
  const amount = parseEuro(fe.betrag.value);

  const fehler = [];
  const mark = (input, msg) => { input.classList.add('invalid'); fehler.push(msg); };
  if (!/^\d{4}-\d{2}-\d{2}$/.test(fe.datum.value)) mark(fe.datum, 'Datum');
  if (!(amount > 0)) mark(fe.betrag, 'Betrag');
  if (isAdvance && !pickedEmployeeId) mark($('#fEmployee'), 'Mitarbeiter (aus der Liste wählen)');
  if (!isAdvance && !fe.partnerName.value.trim()) mark(fe.partnerName, art === 'einnahme' ? 'Name des Zahlers' : 'Name des Empfängers');
  if (!fe.zweck.value.trim()) mark(fe.zweck, 'Verwendungszweck');
  if (!fe.ort.value.trim()) mark(fe.ort, 'Ort');
  if (pad.isEmpty) { $('.sigwrap').classList.add('invalid'); fehler.push('Unterschrift'); }
  if (fehler.length) {
    showError(err, 'Bitte ausfüllen: ' + fehler.join(', ') + '.');
    return;
  }

  const sig = pad.export();
  const payload = {
    receipt_company_id: company.id,
    type: art,
    date: fe.datum.value,
    amount_cents: amount,
    tax: isAdvance ? '0' : fe.ust.value,
    receipt_contact_id: isAdvance ? null : pickedContactId,
    receipt_employee_id: isAdvance ? pickedEmployeeId : null,
    partner_name: isAdvance ? '' : fe.partnerName.value.trim(),
    partner_address: isAdvance ? '' : fe.partnerAdresse.value.trim(),
    purpose: fe.zweck.value.trim(),
    payment_method: fe.zahlungsart.value,
    place: fe.ort.value.trim(),
    signer_name: fe.unterzeichner.value.trim(),
    signature: sig.dataUrl,
    signature_width: Math.max(1, Math.round(sig.w)),
    signature_height: Math.max(1, Math.round(sig.h)),
    corrects_receipt_id: correctsReceipt ? correctsReceipt.id : null,
    remember_contact: !isAdvance && !fe.einmalig.checked
  };

  const btn = $('#btnCreate');
  btn.disabled = true;
  btn.textContent = 'Wird gespeichert …';
  try {
    let r = (await api('POST', 'api/quittungen', payload)).data;
    upsert(S.receipts, r);
    btn.textContent = 'PDF wird erstellt …';
    const buf = buildPdf(pdfData(r, sig.dataUrl));
    try {
      r = await uploadPdf(r, buf);
    } catch (ex) {
      toast('PDF konnte nicht hochgeladen werden – es wird beim nächsten Öffnen neu erzeugt.', 4500);
    }
    pdfCache.set(pdfKey(r), buf);
    lastCreated = r;
    correctsReceipt = null;
    $('#doneNr').textContent = r.number;
    $('#doneInfo').textContent = `${r.company.name} · ${ART_TEXT[r.type]} · ${fmt(r.amount_cents)} € · ${r.partner_name} · ${dmy(r.date)}`;
    formQ.hidden = true;
    $('#doneBox').hidden = false;
    window.scrollTo(0, 0);
    sync(true).catch(() => {});
  } catch (ex) {
    showError(err, ex.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Quittung erstellen (PDF)';
  }
});

$('#btnShare').addEventListener('click', () => lastCreated && receiptPdfAction(lastCreated, 'share'));
$('#btnDownload').addEventListener('click', () => lastCreated && receiptPdfAction(lastCreated, 'dl'));
$('#btnOpen').addEventListener('click', () => lastCreated && receiptPdfAction(lastCreated, 'open'));
$('#btnNew').addEventListener('click', () => {
  lastCreated = null;
  $('#doneBox').hidden = true;
  formQ.hidden = false;
  resetForm();
  window.scrollTo(0, 0);
});

/* =========================================================
   Verlauf
   ========================================================= */

let statusFilter = 'alle';

function receiptBadges(r) {
  const out = [];
  if (r.type === 'vorschuss') out.push(badge('Vorschuss', 'vs'));
  if (r.cancelled_at) out.push(badge('storniert', 'st'));
  else if (r.booked_at) out.push(badge('gebucht', 'gb'));
  if (r.corrects_number) out.push(badge('Korrektur zu ' + r.corrects_number, 'kor'));
  if (r.corrected_by_number) out.push(badge('ersetzt durch ' + r.corrected_by_number, 'kor'));
  return out;
}

function matchesStatus(r) {
  switch (statusFilter) {
    case 'offen': return !r.booked_at && !r.cancelled_at;
    case 'gebucht': return !!r.booked_at && !r.cancelled_at;
    case 'vorschuss': return r.type === 'vorschuss';
    case 'storniert': return !!r.cancelled_at;
    default: return true;
  }
}

function filteredReceipts() {
  const term = $('#search').value.trim().toLowerCase();
  const companyId = Number($('#filterFirma').value) || 0;
  return S.receipts.filter((r) =>
    (!companyId || r.company_id === companyId) &&
    matchesStatus(r) &&
    (!term || [r.number, r.company && r.company.name, r.partner_name, r.purpose, dmy(r.date), fmt(r.amount_cents), r.created_by]
      .join(' ').toLowerCase().includes(term)));
}

function renderCompanyFilter() {
  const filter = $('#filterFirma');
  const before = filter.value;
  filter.innerHTML = '<option value="">Alle Firmen</option>';
  for (const c of S.companies) {
    const o = el('option', null, c.name);
    o.value = c.id;
    filter.appendChild(o);
  }
  filter.value = S.companies.some((c) => String(c.id) === before) ? before : '';
  filter.hidden = S.companies.length < 2;
}

function drawAdvanceSums(items) {
  const box = $('#advanceSums');
  if (statusFilter !== 'vorschuss') { box.hidden = true; return; }
  const month = todayIso().slice(0, 7);
  const sums = new Map();
  for (const r of items) {
    if (r.cancelled_at || !r.date.startsWith(month)) continue;
    sums.set(r.partner_name, (sums.get(r.partner_name) || 0) + r.amount_cents);
  }
  box.innerHTML = '';
  box.appendChild(el('b', null, `Vorschüsse im ${monthName(month)}`));
  if (!sums.size) box.appendChild(el('div', 'words', 'Noch keine.'));
  for (const [name, sum] of [...sums.entries()].sort((a, b) => a[0].localeCompare(b[0], 'de'))) {
    const row = el('div', 'row');
    row.append(el('span', null, name), el('b', null, fmt(sum) + ' €'));
    box.appendChild(row);
  }
  box.hidden = false;
}

function drawList() {
  renderCompanyFilter();
  const list = $('#list');
  const multi = S.companies.length > 1;
  const items = filteredReceipts();
  drawAdvanceSums(items);
  list.innerHTML = '';
  if (!S.ready) {
    list.appendChild(el('div', 'empty', 'Wird geladen …'));
    return;
  }
  if (!items.length) {
    list.appendChild(el('div', 'empty', S.receipts.length ? 'Nichts gefunden.' : 'Noch keine Quittungen erstellt.'));
    return;
  }
  for (const r of items) {
    const item = el('div', 'item clickable' + (r.cancelled_at ? ' storno' : ''));
    if (multi && r.company) item.appendChild(el('div', 'item-firma', r.company.name));
    const top = el('div', 'item-top');
    const amt = el('span', 'item-amt ' + (r.type === 'einnahme' ? 'in' : 'out'), (r.type === 'einnahme' ? '+ ' : '− ') + fmt(r.amount_cents) + ' €');
    top.append(el('span', 'item-nr', r.number), amt);
    item.appendChild(top);
    const badges = receiptBadges(r);
    if (badges.length) {
      const b = el('div', 'item-badges');
      b.append(...badges);
      item.appendChild(b);
    }
    item.appendChild(el('div', 'item-meta', `${dmy(r.date)} · ${r.partner_name} · ${r.purpose}`));
    const edited = (r.changes || []).find((c) => c.action === 'updated');
    item.appendChild(el('div', 'item-by', `erstellt von ${r.created_by}` + (edited ? ` · geändert ${stamp(edited.at)}` : '')));
    const row = el('div', 'btnrow');
    for (const [a, label] of [['open', 'PDF'], ['share', 'Teilen'], ['detail', 'Details …']]) {
      const b = el('button', 'btn small', label);
      b.type = 'button';
      b.addEventListener('click', (ev) => {
        ev.stopPropagation();
        if (a === 'detail') openDetail(r.id);
        else receiptPdfAction(r, a);
      });
      row.appendChild(b);
    }
    item.appendChild(row);
    item.addEventListener('click', () => openDetail(r.id));
    list.appendChild(item);
  }
}
$('#search').addEventListener('input', drawList);
$('#filterFirma').addEventListener('change', drawList);
$('#filterStatus').addEventListener('click', (e) => {
  const chip = e.target.closest('.chip');
  if (!chip) return;
  statusFilter = chip.dataset.filter;
  $('#filterStatus').querySelectorAll('.chip').forEach((c) => c.classList.toggle('active', c === chip));
  drawList();
});

$('#btnCsv').addEventListener('click', () => {
  const items = filteredReceipts();
  if (!items.length) { toast('Keine Quittungen in der Auswahl.'); return; }
  const esc = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
  const rows = [['Firma', 'Nr', 'Datum', 'Art', 'Betrag brutto', 'USt-Satz', 'Netto', 'USt', 'Zahler/Empfänger', 'Verwendungszweck',
    'Zahlungsart', 'Status', 'Storno-Grund', 'Erstellt von', 'Letzte Änderung', 'Dateiname']];
  const sorted = [...items].sort((a, b) => ((a.company && a.company.name) + a.date + a.number).localeCompare((b.company && b.company.name) + b.date + b.number));
  for (const r of sorted) {
    const st = steuer(r.amount_cents, r.tax);
    const status = r.cancelled_at ? 'storniert' : r.booked_at ? 'gebucht' : 'offen';
    rows.push([r.company && r.company.name, r.number, dmy(r.date), ART_TEXT[r.type], fmt(r.amount_cents),
      r.tax === 'ku' ? '§19' : st.satz + ' %', fmt(st.netto), fmt(st.ust), r.partner_name, r.purpose, r.payment_method,
      status, r.cancel_reason || '', r.created_by, stamp(r.updated_at), pdfFileName(pdfData(r, null))]);
  }
  const csv = '﻿' + rows.map((r) => r.map(esc).join(';')).join('\r\n');
  const company = S.companies.find((c) => String(c.id) === $('#filterFirma').value);
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  const a = document.createElement('a');
  a.href = url;
  a.download = `Quittungen_${company ? slug(company.name) + '_' : ''}${todayIso()}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 60000);
});

/* ---------- Dialoge allgemein ---------- */

document.querySelectorAll('dialog').forEach((dlg) => {
  dlg.addEventListener('click', (e) => {
    if (e.target === dlg) dlg.close();
    if (e.target.closest('[data-close]')) dlg.close();
  });
});

/* ---------- Details einer Quittung ---------- */

let detailId = null;

function currentDetail() {
  return S.receipts.find((r) => r.id === detailId) || null;
}

function openDetail(id) {
  detailId = id;
  if (!fillDetail()) return;
  $('#dlgDetail').showModal();
  const r = currentDetail();
  pdfFor(r).catch(() => {}); // vorladen, damit Teilen sofort klappt
}

function addKv(dl, key, value) {
  if (value === null || value === undefined || value === '') return;
  dl.append(el('dt', null, key), el('dd', null, value));
}

function fillDetail() {
  const r = currentDetail();
  const dlg = $('#dlgDetail');
  if (!r) {
    if (dlg.open) dlg.close();
    return false;
  }
  $('#detailNr').textContent = 'Quittung ' + r.number;
  const badges = $('#detailBadges');
  badges.innerHTML = '';
  badges.append(...receiptBadges(r));
  const st = steuer(r.amount_cents, r.tax);
  const info = $('#detailInfo');
  info.innerHTML = '';
  addKv(info, 'Firma', r.company.name);
  addKv(info, 'Art', ART_TEXT[r.type]);
  addKv(info, 'Datum', dmy(r.date));
  addKv(info, 'Betrag', `${fmt(r.amount_cents)} €` + (st.satz ? ` (inkl. ${st.satz} % USt)` : r.tax === 'ku' ? ' (§ 19 UStG)' : ''));
  addKv(info, r.type === 'einnahme' ? 'Gezahlt von' : r.type === 'vorschuss' ? 'Mitarbeiter' : 'Empfänger', r.partner_name);
  addKv(info, 'Anschrift', r.partner_address);
  addKv(info, 'Zweck', r.purpose);
  addKv(info, 'Zahlungsart', r.payment_method);
  addKv(info, 'Ort', r.place);
  addKv(info, 'Unterzeichner', r.signer_name);
  addKv(info, 'Erstellt', `${stamp(r.created_at)} von ${r.created_by}`);
  addKv(info, 'Gebucht', r.booked_at ? stamp(r.booked_at) : 'noch nicht');
  if (r.cancelled_at) addKv(info, 'Storniert', `${stamp(r.cancelled_at)}${r.cancel_reason ? ' – ' + r.cancel_reason : ''}`);

  dlg.querySelector('[data-a="edit"]').hidden = !!r.cancelled_at;
  dlg.querySelector('[data-a="remove"]').hidden = !!r.cancelled_at;
  dlg.querySelector('[data-a="book"]').textContent = r.booked_at ? 'Markierung „gebucht“ entfernen' : 'Als gebucht markieren';

  const log = $('#detailLog');
  log.innerHTML = '';
  for (const c of r.changes || []) {
    log.appendChild(el('li', null, `${stamp(c.at)} · ${c.user} · ${c.label}${c.summary ? ': ' + c.summary : ''}`));
  }
  return true;
}

$('#dlgDetail').addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-a]');
  const r = currentDetail();
  if (!btn || !r) return;
  const a = btn.dataset.a;
  if (a === 'open' || a === 'share' || a === 'dl') receiptPdfAction(r, a);
  if (a === 'edit') openEdit(r);
  if (a === 'remove') openRemove(r);
  if (a === 'book') {
    btn.disabled = true;
    try {
      const updated = (await api('POST', `api/quittungen/${r.id}/gebucht`, { booked: !r.booked_at })).data;
      upsert(S.receipts, updated);
      fillDetail();
      drawList();
      toast(updated.booked_at ? 'Als gebucht markiert.' : 'Markierung entfernt.');
    } catch (ex) {
      toast(ex.message, 4000);
    } finally {
      btn.disabled = false;
    }
  }
});

/* ---------- Bearbeiten ---------- */

const formEdit = $('#formEdit');

function openEdit(r) {
  const fe = formEdit.elements;
  formEdit.dataset.id = r.id;
  formEdit.dataset.updated = r.updated_at;
  $('#editTitle').textContent = `${r.number} bearbeiten`;
  const locked = $('#editLocked');
  locked.innerHTML = '';
  addKv(locked, 'Art', ART_TEXT[r.type]);
  addKv(locked, 'Betrag', fmt(r.amount_cents) + ' €');
  addKv(locked, 'Datum', dmy(r.date));
  addKv(locked, r.type === 'einnahme' ? 'Gezahlt von' : 'Empfänger', r.partner_name);
  fe.partner_address.value = r.partner_address || '';
  fe.purpose.value = r.purpose;
  fe.payment_method.value = r.payment_method;
  fe.place.value = r.place;
  fe.signer_name.value = r.signer_name || '';
  $('#editError').hidden = true;
  $('#dlgEdit').showModal();
}

formEdit.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formEdit.elements;
  const id = Number(formEdit.dataset.id);
  const btn = formEdit.querySelector('[type="submit"]');
  btn.disabled = true;
  try {
    let r = (await api('PUT', `api/quittungen/${id}`, {
      partner_address: fe.partner_address.value.trim(),
      purpose: fe.purpose.value.trim(),
      payment_method: fe.payment_method.value,
      place: fe.place.value.trim(),
      signer_name: fe.signer_name.value.trim(),
      if_unchanged_since: formEdit.dataset.updated
    })).data;
    upsert(S.receipts, r);
    try {
      r = await uploadPdf(r, buildPdf(pdfData(r, await signatureFor(r))));
    } catch (ex) {
      toast('Gespeichert, aber das neue PDF konnte nicht hochgeladen werden.', 4500);
    }
    $('#dlgEdit').close();
    fillDetail();
    drawList();
    toast('Änderung gespeichert.');
  } catch (ex) {
    showError($('#editError'), ex.message);
  } finally {
    btn.disabled = false;
  }
});

/* ---------- Löschen / Stornieren ---------- */

const formRemove = $('#formRemove');

function updateRemoveMode() {
  const cancel = formRemove.elements.mode.value === 'cancel';
  $('#correctRow').hidden = !cancel;
  $('#btnRemove').textContent = cancel ? 'Stornieren' : 'Endgültig löschen';
}

function openRemove(r) {
  formRemove.dataset.id = r.id;
  formRemove.reset();
  $('#removeTitle').textContent = `${r.number} entfernen`;
  const del = formRemove.querySelector('input[value="delete"]');
  del.disabled = !!r.booked_at;
  $('#choiceDelete').classList.toggle('disabled', !!r.booked_at);
  $('#deleteHelp').textContent = r.booked_at
    ? 'Nicht möglich: Die Quittung ist schon als gebucht markiert.'
    : 'Geht, weil sie noch nicht als gebucht markiert ist. Sie verschwindet aus dem Verlauf, im Protokoll bleibt vermerkt, wer sie wann gelöscht hat.';
  formRemove.elements.mode.value = 'cancel';
  updateRemoveMode();
  $('#removeError').hidden = true;
  $('#dlgRemove').showModal();
}

formRemove.addEventListener('change', updateRemoveMode);

formRemove.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formRemove.elements;
  const id = Number(formRemove.dataset.id);
  const r = S.receipts.find((x) => x.id === id);
  const reason = fe.reason.value.trim();
  if (!reason) {
    fe.reason.classList.add('invalid');
    showError($('#removeError'), 'Bitte einen Grund angeben.');
    return;
  }
  const btn = $('#btnRemove');
  btn.disabled = true;
  try {
    if (fe.mode.value === 'delete') {
      await api('DELETE', `api/quittungen/${id}`, { reason });
      S.receipts = S.receipts.filter((x) => x.id !== id);
      S.version = null;
      $('#dlgRemove').close();
      $('#dlgDetail').close();
      drawList();
      toast(`Quittung ${r.number} gelöscht.`);
    } else {
      const updated = (await api('POST', `api/quittungen/${id}/storno`, { reason })).data;
      upsert(S.receipts, updated);
      $('#dlgRemove').close();
      $('#dlgDetail').close();
      drawList();
      if (fe.correct.checked) {
        startCorrection(updated);
        toast('Storniert. Angaben prüfen, Art umstellen und neu unterschreiben lassen.', 5000);
      } else {
        toast(`Quittung ${r.number} storniert.`);
      }
    }
  } catch (ex) {
    showError($('#removeError'), ex.message);
  } finally {
    btn.disabled = false;
  }
});

/* =========================================================
   Stammdaten
   ========================================================= */

let subView = 'firmen';

function showSub(name) {
  subView = name;
  document.querySelectorAll('.segtab').forEach((t) => t.classList.toggle('active', t.dataset.sub === name));
  document.querySelectorAll('.sub').forEach((s) => s.classList.toggle('active', s.id === 'sub-' + name));
  renderStammdaten();
}
document.querySelectorAll('.segtab').forEach((t) => t.addEventListener('click', () => showSub(t.dataset.sub)));

function renderStammdaten() {
  if (subView === 'firmen') renderFirmen();
  if (subView === 'kontakte') renderKontakte();
  if (subView === 'mitarbeiter') renderMitarbeiter();
}

/* ---------- Firmen ---------- */

function renderFirmen() {
  const box = $('#firmenListe');
  box.innerHTML = '';
  if (!S.companies.length) {
    box.appendChild(el('div', 'empty', S.ready ? 'Noch keine Firma angelegt.' : 'Wird geladen …'));
    return;
  }
  const year = new Date().getFullYear();
  const activeId = activeCompanyId();
  for (const c of S.companies) {
    const item = el('div', 'item');
    const top = el('div', 'item-top');
    const name = el('span', 'item-nr', c.name);
    if (c.id === activeId && S.companies.length > 1) name.appendChild(badge('ausgewählt'));
    top.appendChild(name);
    item.append(top, el('div', 'item-meta', [c.street, c.zip_city].filter(Boolean).join(', ')),
      el('div', 'item-meta', `Nächste Nummer: ${formatNr(c.prefix, year, nextSequence(c, year))}`));
    const row = el('div', 'btnrow');
    const edit = el('button', 'btn small', 'Bearbeiten');
    edit.type = 'button';
    edit.addEventListener('click', () => editFirma(c.id));
    const del = el('button', 'btn small danger', 'Entfernen');
    del.type = 'button';
    del.hidden = S.companies.length < 2;
    del.addEventListener('click', () => removeFirma(c.id));
    row.append(edit, del);
    item.appendChild(row);
    box.appendChild(item);
  }
}

/** Vorschlag für ein freies Kürzel bei einer neuen Firma */
function freiesKuerzel() {
  const taken = new Set(S.companies.map((c) => c.prefix.toUpperCase()));
  if (!taken.has('Q')) return 'Q';
  for (let i = 2; i < 100; i++) if (!taken.has('Q' + i)) return 'Q' + i;
  return '';
}

function editFirma(id) {
  const c = id ? S.companies.find((x) => x.id === id) : null;
  const fe = formS.elements;
  formS.dataset.id = c ? c.id : '';
  formS.dataset.updated = c ? c.updated_at : '';
  $('#hFirmaForm').textContent = c ? 'Firma bearbeiten' : (S.companies.length ? 'Weitere Firma anlegen' : 'Erste Firma anlegen');
  ['name', 'street', 'zip_city', 'tax_number', 'vat_id', 'contact', 'default_place'].forEach((k) => { fe[k].value = c ? c[k] || '' : ''; });
  if (!c && S.companies[0]) fe.default_place.value = S.companies[0].default_place || '';
  fe.prefix.value = c ? c.prefix : freiesKuerzel();
  fe.default_tax.value = (c && c.default_tax !== 'ku' && c.default_tax) || '19';
  fe.is_small_business.checked = !!(c && c.is_small_business);
  const next = c ? nextSequence(c, new Date().getFullYear()) : 1;
  fe.next_sequence.value = next;
  formS.dataset.next = String(next);
  formS.querySelectorAll('.invalid').forEach((x) => x.classList.remove('invalid'));
  $('#settingsError').hidden = true;
  $('#btnFirmaAbbrechen').hidden = !S.companies.length;
  updateNumPreview();
  formS.hidden = false;
  formS.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeFirmaForm() {
  formS.hidden = true;
  formS.dataset.id = '';
}

async function removeFirma(id) {
  const c = S.companies.find((x) => x.id === id);
  if (!c || S.companies.length < 2) return;
  if (!await ask(`„${c.name}“ entfernen?\n\nBereits erstellte Quittungen dieser Firma bleiben im Verlauf erhalten.`, 'Entfernen')) return;
  try {
    await api('DELETE', `api/firmen/${id}`);
    S.companies = S.companies.filter((x) => x.id !== id);
    S.version = null;
    if (formS.dataset.id === String(id)) closeFirmaForm();
    updateBrand();
    renderFirmen();
    if (!lastCreated) resetForm();
    toast('Firma entfernt.');
  } catch (ex) {
    toast(ex.message, 4000);
  }
}

function updateNumPreview() {
  const fe = formS.elements;
  const n = parseInt(fe.next_sequence.value, 10) || 1;
  $('#numPreview').textContent = formatNr(fe.prefix.value || 'Q', new Date().getFullYear(), n);
}
formS.elements.prefix.addEventListener('input', updateNumPreview);
formS.elements.next_sequence.addEventListener('input', updateNumPreview);
formS.addEventListener('input', (e) => e.target.classList.remove('invalid'));
$('#btnFirmaNeu').addEventListener('click', () => editFirma(null));
$('#btnFirmaAbbrechen').addEventListener('click', closeFirmaForm);

formS.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formS.elements;
  const id = formS.dataset.id;
  const data = {
    name: fe.name.value.trim(),
    street: fe.street.value.trim(),
    zip_city: fe.zip_city.value.trim(),
    tax_number: fe.tax_number.value.trim(),
    vat_id: fe.vat_id.value.trim(),
    contact: fe.contact.value.trim(),
    default_place: fe.default_place.value.trim(),
    default_tax: fe.default_tax.value,
    is_small_business: fe.is_small_business.checked,
    prefix: fe.prefix.value.trim().replace(/[^\w-]/g, ''),
    next_sequence: fe.next_sequence.value.trim() !== formS.dataset.next ? (parseInt(fe.next_sequence.value, 10) || null) : null,
    if_unchanged_since: formS.dataset.updated || null
  };

  const fehler = [];
  ['name', 'street', 'zip_city', 'prefix'].forEach((k) => {
    fe[k].classList.toggle('invalid', !data[k]);
    if (!data[k]) fehler.push(fe[k].closest('.field').querySelector('span').textContent.replace(' *', ''));
  });
  if (fehler.length) {
    showError($('#settingsError'), 'Bitte ausfüllen: ' + fehler.join(', ') + '.');
    return;
  }

  const first = !S.companies.length;
  const btn = formS.querySelector('[type="submit"]');
  btn.disabled = true;
  try {
    const c = (await api(id ? 'PUT' : 'POST', id ? `api/firmen/${id}` : 'api/firmen', data)).data;
    const i = S.companies.findIndex((x) => x.id === c.id);
    if (i >= 0) S.companies[i] = c;
    else S.companies.push(c);
    S.version = null;
    if (first) setActiveCompany(c.id);
    closeFirmaForm();
    updateBrand();
    renderFirmen();
    if (!lastCreated) resetForm();
    toast(id ? 'Firma gespeichert.' : `„${c.name}“ angelegt.`);
    if (first) showView('neu');
  } catch (ex) {
    showError($('#settingsError'), ex.message);
  } finally {
    btn.disabled = false;
  }
});

/* ---------- Kontakte & DJs ---------- */

let contactFilter = 'alle';
let contactId = null;
const formContact = $('#formContact');

function renderKontakte() {
  const term = $('#kontaktSuche').value.trim().toLowerCase();
  const box = $('#kontaktListe');
  const items = S.contacts.filter((c) =>
    (contactFilter === 'alle' || (contactFilter === 'dj') === c.is_dj) &&
    (!term || [c.name, c.real_name, c.address, c.technical_rider, c.hospitality_rider, c.notes].join(' ').toLowerCase().includes(term)));
  $('#kontakteAnzahl').textContent = `(${S.contacts.length})`;
  box.innerHTML = '';
  if (!items.length) {
    box.appendChild(el('div', 'empty', S.contacts.length ? 'Nichts gefunden.' : 'Noch keine Kontakte. Sie entstehen automatisch beim Erstellen einer Quittung oder über „+ Neu“.'));
    return;
  }
  for (const c of items) {
    const row = el('div', 'person');
    const text = el('div', 'person-text');
    const name = el('b', null, c.name);
    if (c.is_dj) name.append(' ', badge('DJ', 'dj'));
    const rider = c.is_dj ? ((c.technical_rider || c.hospitality_rider || c.files.length) ? 'Rider vorhanden' : 'kein Rider') : '';
    text.append(name, el('small', null, [oneLine(c.address), rider].filter(Boolean).join(' · ') || '–'));
    row.append(el('span', 'av', initials(c.name)), text);
    row.addEventListener('click', () => openContact(c.id));
    box.appendChild(row);
  }
}
$('#kontaktSuche').addEventListener('input', renderKontakte);
$('#kontaktFilter').addEventListener('click', (e) => {
  const chip = e.target.closest('.chip');
  if (!chip) return;
  contactFilter = chip.dataset.filter;
  $('#kontaktFilter').querySelectorAll('.chip').forEach((c) => c.classList.toggle('active', c === chip));
  renderKontakte();
});
$('#btnKontaktNeu').addEventListener('click', () => openContact(null));

function toggleRider() {
  const dj = formContact.elements.is_dj.checked;
  $('#riderFields').hidden = !dj;
  $('#filesTitle').textContent = dj ? 'Rider-Dateien' : 'Dateien';
}
formContact.elements.is_dj.addEventListener('change', toggleRider);

function renderContactFiles(c) {
  const list = $('#fileList');
  list.innerHTML = '';
  $('#filesHint').hidden = !!c;
  $('#fileUpload').closest('label').hidden = !c;
  if (!c) return;
  for (const f of c.files) {
    const row = el('div', 'file');
    const name = el('div', 'file-name');
    name.append(el('b', null, f.name), el('small', null, `${stamp(f.created_at)} · ${Math.max(1, Math.round(f.size / 1024))} KB`));
    const open = el('button', 'btn small', 'Öffnen');
    open.type = 'button';
    open.addEventListener('click', () => openBlobLater(() => fetchBlob(f.url), (blob) => downloadPdf({ pdf: blob, dateiname: f.name })));
    const del = el('button', 'btn small danger', 'Löschen');
    del.type = 'button';
    del.addEventListener('click', async () => {
      if (!await ask(`„${f.name}“ löschen?`, 'Löschen')) return;
      try {
        const updated = (await api('DELETE', `api/kontakte/${c.id}/dateien/${f.id}`)).data;
        upsert(S.contacts, updated);
        sortByName(S.contacts);
        formContact.dataset.updated = updated.updated_at;
        renderContactFiles(updated);
      } catch (ex) {
        toast(ex.message, 4000);
      }
    });
    row.append(name, open, del);
    list.appendChild(row);
  }
}

function renderPersonReceipts(box, receipts) {
  box.innerHTML = '';
  if (!receipts.length) return;
  box.appendChild(el('h2', null, 'Quittungen'));
  const ul = el('ul', 'mini-list');
  for (const r of receipts.slice(0, 15)) {
    ul.appendChild(el('li', null, `${dmy(r.date)} · ${r.number} · ${r.type === 'einnahme' ? '+' : '−'}${fmt(r.amount_cents)} €${r.cancelled_at ? ' (storniert)' : ''}`));
  }
  box.appendChild(ul);
}

function openContact(id) {
  const c = id ? S.contacts.find((x) => x.id === id) : null;
  contactId = c ? c.id : null;
  const fe = formContact.elements;
  formContact.dataset.updated = c ? c.updated_at : '';
  $('#contactTitle').textContent = c ? c.name : 'Neuer Kontakt';
  ['name', 'real_name', 'address', 'phone', 'email', 'technical_rider', 'hospitality_rider', 'notes'].forEach((k) => { fe[k].value = c ? c[k] || '' : ''; });
  fe.is_dj.checked = c ? c.is_dj : contactFilter === 'dj';
  toggleRider();
  renderContactFiles(c);
  renderPersonReceipts($('#contactReceipts'), c ? S.receipts.filter((r) => r.contact_id === c.id) : []);
  $('#btnContactDelete').hidden = !c;
  $('#contactError').hidden = true;
  formContact.querySelectorAll('.invalid').forEach((x) => x.classList.remove('invalid'));
  $('#dlgContact').showModal();
}

formContact.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formContact.elements;
  if (!fe.name.value.trim()) {
    fe.name.classList.add('invalid');
    showError($('#contactError'), 'Bitte einen Namen eingeben.');
    return;
  }
  const data = { is_dj: fe.is_dj.checked, if_unchanged_since: formContact.dataset.updated || null };
  ['name', 'real_name', 'address', 'phone', 'email', 'technical_rider', 'hospitality_rider', 'notes'].forEach((k) => { data[k] = fe[k].value.trim(); });
  const isNew = !contactId;
  const btn = formContact.querySelector('[type="submit"]');
  btn.disabled = true;
  try {
    const c = (await api(isNew ? 'POST' : 'PUT', isNew ? 'api/kontakte' : `api/kontakte/${contactId}`, data)).data;
    upsert(S.contacts, c);
    sortByName(S.contacts);
    renderKontakte();
    if (isNew && c.is_dj) {
      contactId = c.id;
      formContact.dataset.updated = c.updated_at;
      $('#contactTitle').textContent = c.name;
      $('#btnContactDelete').hidden = false;
      renderContactFiles(c);
      toast('Gespeichert. Jetzt können Rider-Dateien hochgeladen werden.', 4000);
    } else {
      $('#dlgContact').close();
      toast('Gespeichert.');
    }
  } catch (ex) {
    showError($('#contactError'), ex.message);
  } finally {
    btn.disabled = false;
  }
});

$('#fileUpload').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  e.target.value = '';
  if (!file || !contactId) return;
  const fd = new FormData();
  fd.append('file', file);
  toast('Wird hochgeladen …', 10000);
  try {
    const c = (await api('POST', `api/kontakte/${contactId}/dateien`, fd)).data;
    upsert(S.contacts, c);
    sortByName(S.contacts);
    formContact.dataset.updated = c.updated_at;
    renderContactFiles(c);
    toast('Datei hochgeladen.');
  } catch (ex) {
    toast(ex.message, 5000);
  }
});

$('#btnContactDelete').addEventListener('click', async () => {
  const c = S.contacts.find((x) => x.id === contactId);
  if (!c || !await ask(`„${c.name}“ löschen?\n\nQuittungen mit diesem Namen bleiben erhalten.`, 'Löschen')) return;
  try {
    await api('DELETE', `api/kontakte/${c.id}`);
    S.contacts = S.contacts.filter((x) => x.id !== c.id);
    S.version = null;
    $('#dlgContact').close();
    renderKontakte();
    toast('Kontakt gelöscht.');
  } catch (ex) {
    toast(ex.message, 4000);
  }
});

/* ---------- Mitarbeiter ---------- */

let employeeId = null;
const formEmployee = $('#formEmployee');

function renderMitarbeiter() {
  const term = $('#maSuche').value.trim().toLowerCase();
  const box = $('#maListe');
  const year = String(new Date().getFullYear());
  $('#mitarbeiterAnzahl').textContent = `(${S.employees.length})`;
  box.innerHTML = '';
  const items = S.employees.filter((e) => !term || [e.name, e.note].join(' ').toLowerCase().includes(term));
  if (!items.length) {
    box.appendChild(el('div', 'empty', S.employees.length ? 'Nichts gefunden.' : 'Noch keine Mitarbeiter angelegt.'));
    return;
  }
  for (const e of items) {
    const sum = advanceSum(e.id, year);
    const row = el('div', 'person');
    const text = el('div', 'person-text');
    text.append(el('b', null, e.name), el('small', null, [e.note, sum ? `Vorschüsse ${year}: ${fmt(sum)} €` : ''].filter(Boolean).join(' · ') || '–'));
    row.append(el('span', 'av', initials(e.name)), text);
    row.addEventListener('click', () => openEmployee(e.id));
    box.appendChild(row);
  }
}
$('#maSuche').addEventListener('input', renderMitarbeiter);

$('#formMaNeu').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = e.target.elements;
  const name = fe.name.value.trim();
  if (!name) { fe.name.classList.add('invalid'); return; }
  try {
    const created = (await api('POST', 'api/mitarbeiter', { name, note: fe.note.value.trim() })).data;
    S.employees.push(created);
    sortByName(S.employees);
    S.version = null;
    e.target.reset();
    renderMitarbeiter();
    toast(`${created.name} angelegt.`);
  } catch (ex) {
    toast(ex.message, 4000);
  }
});

function openEmployee(id) {
  const e = S.employees.find((x) => x.id === id);
  if (!e) return;
  employeeId = e.id;
  formEmployee.dataset.updated = e.updated_at;
  formEmployee.elements.name.value = e.name;
  formEmployee.elements.note.value = e.note || '';
  $('#employeeTitle').textContent = e.name;
  renderPersonReceipts($('#employeeReceipts'), S.receipts.filter((r) => r.employee_id === e.id));
  $('#employeeError').hidden = true;
  $('#dlgEmployee').showModal();
}

formEmployee.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formEmployee.elements;
  if (!fe.name.value.trim()) {
    fe.name.classList.add('invalid');
    showError($('#employeeError'), 'Bitte einen Namen eingeben.');
    return;
  }
  try {
    const updated = (await api('PUT', `api/mitarbeiter/${employeeId}`, {
      name: fe.name.value.trim(), note: fe.note.value.trim(), if_unchanged_since: formEmployee.dataset.updated
    })).data;
    upsert(S.employees, updated);
    sortByName(S.employees);
    $('#dlgEmployee').close();
    renderMitarbeiter();
    toast('Gespeichert.');
  } catch (ex) {
    showError($('#employeeError'), ex.message);
  }
});

$('#btnEmployeeDelete').addEventListener('click', async () => {
  const e = S.employees.find((x) => x.id === employeeId);
  if (!e || !await ask(`„${e.name}“ löschen?\n\nVorschuss-Quittungen bleiben erhalten.`, 'Löschen')) return;
  try {
    await api('DELETE', `api/mitarbeiter/${e.id}`);
    S.employees = S.employees.filter((x) => x.id !== e.id);
    S.version = null;
    if (pickedEmployeeId === e.id) { pickedEmployeeId = null; $('#fEmployee').value = ''; }
    $('#dlgEmployee').close();
    renderMitarbeiter();
    toast('Mitarbeiter gelöscht.');
  } catch (ex) {
    toast(ex.message, 4000);
  }
});

/* =========================================================
   Neuer Stand vom Server
   ========================================================= */

function onDataChanged(first) {
  updateBrand();
  if (formQ.hidden === false) {
    renderFirmaSelect();
    setNummer();
    if (first) resetForm();
    else applyArt(false);
  }
  const view = currentView();
  if (view === 'verlauf') drawList();
  if (view === 'stammdaten') renderStammdaten();
  if ($('#dlgDetail').open) fillDetail();
  if (first && !S.companies.length) {
    showView('stammdaten');
    showSub('firmen');
    editFirma(null);
    toast('Willkommen! Bitte zuerst die Firma anlegen.', 4000);
  }
}

/* =========================================================
   Umzug: Daten der alten Version (nur auf diesem Gerät)
   ========================================================= */

const LS_KEY = 'quittung.einstellungen.v2';
const LS_KEY_V1 = 'quittung.einstellungen.v1';
const LS_DONE = 'quittung.umzug.erledigt';
const DB_NAME = 'quittungen';
const STORE = 'belege';

function loadLegacySettings() {
  let s = null;
  try { s = JSON.parse(localStorage.getItem(LS_KEY) || 'null'); } catch (e) { s = null; }
  if (!s || typeof s !== 'object') {
    s = { firmen: [], altFirmaId: '' };
    let alt = null;
    try { alt = JSON.parse(localStorage.getItem(LS_KEY_V1) || 'null'); } catch (e) { alt = null; }
    if (alt && alt.firma) {
      s.firmen.push(Object.assign({}, alt, { id: 'v1' }));
      s.altFirmaId = 'v1';
    }
  }
  s.firmen = Array.isArray(s.firmen) ? s.firmen.filter((f) => f && f.firma) : [];
  return s;
}

function legacyReceipts() {
  return new Promise((resolve) => {
    if (!window.indexedDB) { resolve([]); return; }
    let req;
    try { req = indexedDB.open(DB_NAME, 1); } catch (e) { resolve([]); return; }
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) db.createObjectStore(STORE, { keyPath: 'id' }).createIndex('nr', 'nr', { unique: true });
    };
    req.onerror = () => resolve([]);
    req.onsuccess = () => {
      try {
        const all = req.result.transaction(STORE, 'readonly').objectStore(STORE).getAll();
        all.onsuccess = () => resolve(all.result || []);
        all.onerror = () => resolve([]);
      } catch (e) { resolve([]); }
    };
  });
}

function importDone() {
  try { return localStorage.getItem(LS_DONE) === '1' || sessionStorage.getItem(LS_DONE) === 'spaeter'; } catch (e) { return true; }
}

async function checkImport() {
  if (importDone()) return;
  const legacy = loadLegacySettings();
  const belege = await legacyReceipts();
  if (!legacy.firmen.length && !belege.length) return;
  $('#importFirmen').textContent = legacy.firmen.length;
  $('#importBelege').textContent = belege.length;
  $('#importBox').hidden = false;
}

$('#btnImportLater').addEventListener('click', () => {
  try { sessionStorage.setItem(LS_DONE, 'spaeter'); } catch (e) { /* egal */ }
  $('#importBox').hidden = true;
});

$('#btnImport').addEventListener('click', async () => {
  const btn = $('#btnImport');
  btn.disabled = true;
  const legacy = loadLegacySettings();
  const belege = await legacyReceipts();
  let imported = 0, skipped = 0, failed = 0;
  try {
    btn.textContent = 'Firmen werden übernommen …';
    const map = legacy.firmen.length ? (await api('POST', 'api/umzug/firmen', {
      companies: legacy.firmen.map((f) => ({
        id: String(f.id), firma: f.firma, strasse: f.strasse || '', plzOrt: f.plzOrt || '', steuernr: f.steuernr || '',
        ustid: f.ustid || '', kontakt: f.kontakt || '', kleinunternehmer: !!f.kleinunternehmer, ort: f.ort || '',
        standardUst: f.standardUst || '19', praefix: String(f.praefix || 'Q').replace(/[^\w-]/g, '') || 'Q',
        zaehler: Object.fromEntries(Object.entries(f.zaehler || {}).map(([y, n]) => [y, parseInt(n, 10) || 0]))
      }))
    })).companies : {};

    for (let i = 0; i < belege.length; i++) {
      const q = belege[i];
      btn.textContent = `Quittung ${i + 1} von ${belege.length} …`;
      const fd = new FormData();
      const add = (k, v) => { if (v !== undefined && v !== null && v !== '') fd.append(k, v); };
      const firmaId = q.firmaId || legacy.altFirmaId || '';
      add('number', q.nr);
      add('receipt_company_id', map[firmaId]);
      add('type', q.art === 'einnahme' ? 'einnahme' : 'ausgabe');
      add('date', q.datum);
      add('amount_cents', q.betrag);
      add('tax', q.ust);
      add('partner_name', q.partnerName);
      add('partner_address', q.partnerAdresse);
      add('purpose', q.zweck);
      add('payment_method', q.zahlungsart);
      add('place', q.ort);
      add('signer_name', q.unterzeichner);
      if (q.unterschrift) {
        add('signature', q.unterschrift.dataUrl);
        add('signature_width', Math.max(1, Math.round(q.unterschrift.w)));
        add('signature_height', Math.max(1, Math.round(q.unterschrift.h)));
      }
      for (const [k, v] of Object.entries(q.firma || {})) add(`company[${k}]`, v);
      add('created_at', q.erstellt);
      if (q.pdf) fd.append('pdf', new Blob([q.pdf], { type: 'application/pdf' }), q.dateiname || 'quittung.pdf');
      try {
        const res = await api('POST', 'api/umzug/quittungen', fd);
        if (res.imported) imported++; else skipped++;
      } catch (ex) {
        if (ex.status === 0 || ex.status === 401) throw ex;
        failed++;
      }
    }

    if (!failed) {
      try { localStorage.setItem(LS_DONE, '1'); } catch (e) { /* egal */ }
      $('#importBox').hidden = true;
    }
    toast(`${imported} Quittungen übernommen` + (skipped ? `, ${skipped} waren schon da` : '') + (failed ? `, ${failed} fehlgeschlagen` : '') + '.', 6000);
    await sync(true);
  } catch (ex) {
    toast(ex.message, 6000);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Jetzt übernehmen';
  }
});

/* =========================================================
   Start
   ========================================================= */

async function init() {
  setSync('loading');
  resetForm();
  try {
    await sync(true);
  } catch (e) {
    if (e.status !== 401) toast(e.message, 5000);
  }
  checkImport();

  // Abgleich: alle 30 Sekunden, beim Zurückkehren in die App und wenn das Netz wieder da ist
  setInterval(() => {
    if (document.visibilityState === 'visible') sync().catch(() => {});
  }, 30000);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') sync().catch(() => {});
  });
  window.addEventListener('online', () => sync().catch(() => {}));

  if ('serviceWorker' in navigator && location.protocol.startsWith('http')) {
    // Läuft die App unter einer Unterseite (z. B. /admin/quittung), gibt der Server den Bereich vor
    const scopeMeta = document.querySelector('meta[name="sw-scope"]');
    const options = scopeMeta ? { scope: scopeMeta.content } : undefined;
    navigator.serviceWorker.register('sw.js', options).catch(() => {});
  }
}

if (!window.jspdf) {
  document.body.insertAdjacentHTML('afterbegin', '<div class="error">PDF-Bibliothek konnte nicht geladen werden.</div>');
}
init();
