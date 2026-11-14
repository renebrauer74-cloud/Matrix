/* Quittungsprogramm – läuft komplett im Browser, keine Daten verlassen das Gerät. */
'use strict';

const APP_VERSION = '1.0.0';

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

const $ = (sel, root = document) => root.querySelector(sel);

function toast(msg, ms = 2600) {
  const t = $('#toast');
  t.textContent = msg;
  t.hidden = false;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => { t.hidden = true; }, ms);
}

/* =========================================================
   Speicher: Einstellungen (localStorage), Belege (IndexedDB)
   ========================================================= */

const LS_KEY = 'quittung.einstellungen.v1';
const DEFAULTS = {
  firma: '', strasse: '', plzOrt: '', steuernr: '', ustid: '', kontakt: '',
  kleinunternehmer: false, ort: '', standardUst: '19', praefix: 'Q', zaehler: {}
};

function loadSettings() {
  try {
    const raw = JSON.parse(localStorage.getItem(LS_KEY) || '{}');
    return Object.assign({}, DEFAULTS, raw, { zaehler: Object.assign({}, raw.zaehler || {}) });
  } catch (e) {
    return Object.assign({}, DEFAULTS, { zaehler: {} });
  }
}
function saveSettings(s) {
  try { localStorage.setItem(LS_KEY, JSON.stringify(s)); return true; } catch (e) { return false; }
}

const DB_NAME = 'quittungen';
const STORE = 'belege';
let dbPromise = null;

function openDB() {
  if (dbPromise) return dbPromise;
  dbPromise = new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) {
        const st = db.createObjectStore(STORE, { keyPath: 'id' });
        st.createIndex('nr', 'nr', { unique: true });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => { dbPromise = null; reject(req.error); };
  });
  return dbPromise;
}
async function dbAdd(obj) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, 'readwrite');
    tx.objectStore(STORE).add(obj);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
    tx.onabort = () => reject(tx.error);
  });
}
async function dbAll() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const req = db.transaction(STORE, 'readonly').objectStore(STORE).getAll();
    req.onsuccess = () => resolve(req.result || []);
    req.onerror = () => reject(req.error);
  });
}

/* =========================================================
   Nummernkreis
   ========================================================= */

function formatNr(praefix, jahr, n) {
  return `${(praefix || 'Q').trim()}-${jahr}-${String(n).padStart(4, '0')}`;
}

/** Nächste freie laufende Nummer für ein Jahr (berücksichtigt auch bereits gespeicherte Belege) */
async function naechsteNummer(jahr) {
  const s = loadSettings();
  let n = (s.zaehler[jahr] || 0) + 1;
  try {
    const all = await dbAll();
    const used = new Set(all.map((q) => q.nr));
    while (used.has(formatNr(s.praefix, jahr, n))) n++;
  } catch (e) { /* ohne DB nur Zähler verwenden */ }
  return n;
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

/* =========================================================
   PDF-Erzeugung
   ========================================================= */

function buildPdf(q) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
  const F = q.firma;           // Firmendaten (Momentaufnahme beim Erstellen)
  const st = steuer(q.betrag, q.ust);
  const L = 20, R = 190, W = R - L;
  const artText = q.art === 'einnahme' ? 'Einnahme' : 'Ausgabe';

  doc.setProperties({
    title: `Quittung ${q.nr}`,
    subject: `${artText} ${fmt(q.betrag)} EUR, ${dmy(q.datum)}: ${q.zweck}`,
    author: F.firma,
    keywords: `Quittung, Barbeleg, Kassenbuch, ${artText}, ${q.nr}`,
    creator: `Quittungsprogramm ${APP_VERSION}`
  });

  const gray = () => doc.setTextColor(95, 105, 98);
  const black = () => doc.setTextColor(15, 20, 17);

  // ---------- Kopf ----------
  let y = 24;
  doc.setFont('helvetica', 'bold');
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
  doc.text(`${artText} – Zahlungsart: ${q.zahlungsart}`, L, y);

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
  row('Zahlungsempfänger:', empfaenger, true);
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
    doc.addImage(q.unterschrift.dataUrl, 'JPEG', sx + (sw - w) / 2, lineY - h - 0.5, w, h, undefined, 'FAST');
  }
  doc.line(sx, lineY, R, lineY);
  doc.text('Unterschrift Zahlungsempfänger', sx, lineY + 4.5);
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

  return doc.output('arraybuffer');
}

function pdfFileName(q) {
  return `Quittung_${slug(q.nr)}_${q.datum}_${slug(q.partnerName) || 'Beleg'}.pdf`;
}

/* =========================================================
   PDF teilen / speichern / öffnen
   ========================================================= */

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

function openPdf(q) {
  const url = URL.createObjectURL(pdfBlob(q));
  const w = window.open(url, '_blank');
  if (!w) downloadPdf(q);
  setTimeout(() => URL.revokeObjectURL(url), 120000);
}

async function sharePdf(q) {
  const file = new File([q.pdf], q.dateiname, { type: 'application/pdf' });
  if (navigator.canShare && navigator.canShare({ files: [file] })) {
    try {
      await navigator.share({
        files: [file],
        title: `Quittung ${q.nr}`,
        text: `Quittung ${q.nr} vom ${dmy(q.datum)} über ${fmt(q.betrag)} EUR`
      });
    } catch (e) {
      if (e && e.name !== 'AbortError') { toast('Teilen nicht möglich – PDF wird gespeichert.'); downloadPdf(q); }
    }
  } else {
    toast('Teilen wird hier nicht unterstützt – PDF wird gespeichert.');
    downloadPdf(q);
  }
}

/* =========================================================
   Oberfläche
   ========================================================= */

const formQ = $('#formQ');
const formS = $('#formS');
const pad = new SignaturePad($('#sigCanvas'));
let lastCreated = null;

function showView(name) {
  document.querySelectorAll('.view').forEach((v) => v.classList.toggle('active', v.id === 'view-' + name));
  document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t.dataset.view === name));
  if (name === 'verlauf') renderList();
  if (name === 'einstellungen') fillSettingsForm();
  if (name === 'neu') requestAnimationFrame(() => pad.resize());
  window.scrollTo(0, 0);
}
document.querySelectorAll('.tab').forEach((t) => t.addEventListener('click', () => showView(t.dataset.view)));

function settingsComplete(s) {
  return !!(s.firma && s.strasse && s.plzOrt);
}

function currentArt() {
  return formQ.elements.art.value;
}

function updateBrand() {
  const s = loadSettings();
  $('#brandFirma').textContent = s.firma || 'Firmendaten fehlen';
}

/** Felder passend zu Einnahme/Ausgabe beschriften */
function applyArt(setDefaults) {
  const s = loadSettings();
  const art = currentArt();
  const firmaTxt = [s.firma, s.strasse, s.plzOrt].filter(Boolean).join(', ') || '– bitte unter „Firma“ eintragen –';
  if (art === 'einnahme') {
    $('#hPartner').textContent = 'Gezahlt von (Kunde / Zahler)';
    $('#fixedParty').innerHTML = '<b>Empfänger (wir)</b>';
    $('#hSig').textContent = 'Unterschrift Empfänger (für die Firma)';
    $('#sigHelp').textContent = 'Wer das Geld für die Firma angenommen hat, unterschreibt hier mit dem Finger.';
    $('#lblSigner').textContent = 'Name des Unterzeichners (Mitarbeiter)';
  } else {
    $('#hPartner').textContent = 'Zahlungsempfänger (bekommt das Geld)';
    $('#fixedParty').innerHTML = '<b>Gezahlt von (wir)</b>';
    $('#hSig').textContent = 'Unterschrift Zahlungsempfänger';
    $('#sigHelp').textContent = 'Die Person, die das Geld bekommt, unterschreibt hier mit dem Finger.';
    $('#lblSigner').textContent = 'Name in Druckbuchstaben';
  }
  $('#fixedParty').append(document.createTextNode(firmaTxt));
  if (setDefaults) {
    $('#fUst').value = art === 'einnahme' ? (s.kleinunternehmer ? 'ku' : (s.standardUst || '19')) : '0';
  }
  updateCalc();
}

function updateCalc() {
  const cents = parseEuro($('#fBetrag').value);
  const box = $('#calcBox');
  const s = loadSettings();
  if (!(cents > 0)) { box.hidden = true; } else {
    const st = steuer(cents, $('#fUst').value);
    box.innerHTML = '';
    const add = (a, b) => {
      const r = document.createElement('div');
      r.className = 'row';
      r.innerHTML = '<span></span><b></b>';
      r.children[0].textContent = a;
      r.children[1].textContent = b;
      box.appendChild(r);
    };
    if (st.satz) {
      add('Netto', fmt(st.netto) + ' €');
      add(`USt ${st.satz} %`, fmt(st.ust) + ' €');
    }
    add('Gesamt', fmt(cents) + ' €');
    const w = document.createElement('div');
    w.className = 'words';
    w.textContent = betragInWorten(cents);
    box.appendChild(w);
    box.hidden = false;
  }

  // Hinweis bei Einnahmen über 250 € (Kleinbetragsrechnung überschritten)
  const hint = $('#hint250');
  const fehlt = [];
  if (currentArt() === 'einnahme' && cents > 25000) {
    if (!s.steuernr && !s.ustid) fehlt.push('Steuernummer oder USt-IdNr. (unter „Firma“)');
    if (!$('#fPartnerAdresse').value.trim()) fehlt.push('Anschrift des Kunden');
  }
  if (fehlt.length) {
    hint.textContent = 'Über 250 € gelten die vollen Rechnungsangaben. Es fehlt: ' + fehlt.join(', ') + '.';
    hint.hidden = false;
  } else hint.hidden = true;
}

async function resetForm() {
  const s = loadSettings();
  formQ.reset();
  $('#fDatum').value = todayIso();
  $('#fOrt').value = s.ort || '';
  const jahr = new Date().getFullYear();
  $('#fNr').value = formatNr(s.praefix, jahr, await naechsteNummer(jahr));
  pad.clear();
  formQ.querySelectorAll('.invalid').forEach((el) => el.classList.remove('invalid'));
  $('#formError').hidden = true;
  applyArt(true);
}

function showError(el, msg) {
  el.textContent = msg;
  el.hidden = false;
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

formQ.addEventListener('change', (e) => {
  if (e.target.name === 'art') applyArt(true);
  if (e.target.name === 'ust') updateCalc();
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
$('#btnReset').addEventListener('click', () => {
  if (confirm('Alle Eingaben verwerfen?')) resetForm();
});

formQ.addEventListener('submit', async (e) => {
  e.preventDefault();
  const s = loadSettings();
  const err = $('#formError');
  err.hidden = true;
  formQ.querySelectorAll('.invalid').forEach((el) => el.classList.remove('invalid'));
  $('.sigwrap').classList.remove('invalid');

  if (!settingsComplete(s)) {
    showError(err, 'Bitte zuerst unter „Firma“ Firmenname und Anschrift eintragen.');
    return;
  }

  const fe = formQ.elements;
  const q = {
    art: currentArt(),
    nr: fe.nr.value.trim(),
    datum: fe.datum.value,
    betrag: parseEuro(fe.betrag.value),
    ust: fe.ust.value,
    partnerName: fe.partnerName.value.trim(),
    partnerAdresse: fe.partnerAdresse.value.trim(),
    zweck: fe.zweck.value.trim(),
    zahlungsart: fe.zahlungsart.value,
    ort: fe.ort.value.trim(),
    unterzeichner: fe.unterzeichner.value.trim()
  };

  const fehler = [];
  const mark = (el, msg) => { el.classList.add('invalid'); fehler.push(msg); };
  if (!q.nr) mark(fe.nr, 'Quittungs-Nr.');
  if (!/^\d{4}-\d{2}-\d{2}$/.test(q.datum)) mark(fe.datum, 'Datum');
  if (!(q.betrag > 0)) mark(fe.betrag, 'Betrag');
  if (!q.partnerName) mark(fe.partnerName, q.art === 'einnahme' ? 'Name des Zahlers' : 'Name des Empfängers');
  if (!q.zweck) mark(fe.zweck, 'Verwendungszweck');
  if (!q.ort) mark(fe.ort, 'Ort');
  if (pad.isEmpty) { $('.sigwrap').classList.add('invalid'); fehler.push('Unterschrift'); }
  if (fehler.length) {
    showError(err, 'Bitte ausfüllen: ' + fehler.join(', ') + '.');
    return;
  }

  const btn = $('#btnCreate');
  btn.disabled = true;
  btn.textContent = 'PDF wird erstellt…';
  try {
    const all = await dbAll();
    if (all.some((x) => x.nr === q.nr)) {
      fe.nr.classList.add('invalid');
      showError(err, `Die Nummer ${q.nr} wurde bereits vergeben. Bitte eine andere Nummer verwenden.`);
      return;
    }

    q.id = (crypto.randomUUID && crypto.randomUUID()) || String(Date.now()) + Math.random().toString(16).slice(2);
    q.unterschrift = pad.export();
    q.firma = {
      firma: s.firma, strasse: s.strasse, plzOrt: s.plzOrt,
      steuernr: s.steuernr, ustid: s.ustid, kontakt: s.kontakt
    };
    q.erstellt = new Date().toISOString();
    q.erstelltText = nowStamp();
    q.dateiname = pdfFileName(q);
    q.pdf = buildPdf(q);

    await dbAdd(q);

    // Zähler fortschreiben (falls die Nummer zum Schema passt)
    const m = q.nr.match(/-(\d{4})-(\d+)$/);
    if (m) {
      const jahr = m[1], n = parseInt(m[2], 10);
      if (!s.zaehler[jahr] || n > s.zaehler[jahr]) s.zaehler[jahr] = n;
      saveSettings(s);
    }

    lastCreated = q;
    $('#doneNr').textContent = q.nr;
    $('#doneInfo').textContent = `${q.art === 'einnahme' ? 'Einnahme' : 'Ausgabe'} · ${fmt(q.betrag)} € · ${q.partnerName} · ${dmy(q.datum)}`;
    formQ.hidden = true;
    $('#doneBox').hidden = false;
    window.scrollTo(0, 0);
  } catch (ex) {
    console.error(ex);
    showError(err, 'Fehler beim Erstellen: ' + (ex && ex.message ? ex.message : ex));
  } finally {
    btn.disabled = false;
    btn.textContent = 'Quittung erstellen (PDF)';
  }
});

$('#btnShare').addEventListener('click', () => lastCreated && sharePdf(lastCreated));
$('#btnDownload').addEventListener('click', () => lastCreated && downloadPdf(lastCreated));
$('#btnOpen').addEventListener('click', () => lastCreated && openPdf(lastCreated));
$('#btnNew').addEventListener('click', async () => {
  lastCreated = null;
  $('#doneBox').hidden = true;
  formQ.hidden = false;
  await resetForm();
  window.scrollTo(0, 0);
});

/* ---------- Verlauf ---------- */

let listCache = [];

async function renderList() {
  const list = $('#list');
  try {
    listCache = (await dbAll()).sort((a, b) => (b.erstellt || '').localeCompare(a.erstellt || ''));
  } catch (e) {
    list.innerHTML = '<div class="empty">Speicher nicht verfügbar.</div>';
    return;
  }
  drawList();
}

function drawList() {
  const list = $('#list');
  const term = $('#search').value.trim().toLowerCase();
  const items = listCache.filter((q) => !term ||
    [q.nr, q.partnerName, q.zweck, dmy(q.datum), fmt(q.betrag)].join(' ').toLowerCase().includes(term));
  list.innerHTML = '';
  if (!items.length) {
    list.innerHTML = `<div class="empty">${listCache.length ? 'Nichts gefunden.' : 'Noch keine Quittungen erstellt.'}</div>`;
    return;
  }
  for (const q of items) {
    const el = document.createElement('div');
    el.className = 'item';
    el.innerHTML = `
      <div class="item-top"><span class="item-nr"></span><span class="item-amt"></span></div>
      <div class="item-meta"></div>
      <div class="btnrow">
        <button type="button" class="btn small" data-a="share">Teilen</button>
        <button type="button" class="btn small" data-a="dl">Speichern</button>
        <button type="button" class="btn small" data-a="open">Ansehen</button>
      </div>`;
    $('.item-nr', el).textContent = q.nr;
    const amt = $('.item-amt', el);
    amt.textContent = (q.art === 'einnahme' ? '+ ' : '− ') + fmt(q.betrag) + ' €';
    amt.classList.add(q.art === 'einnahme' ? 'in' : 'out');
    $('.item-meta', el).textContent = `${dmy(q.datum)} · ${q.partnerName} · ${q.zweck}`;
    el.querySelector('[data-a="share"]').addEventListener('click', () => sharePdf(q));
    el.querySelector('[data-a="dl"]').addEventListener('click', () => downloadPdf(q));
    el.querySelector('[data-a="open"]').addEventListener('click', () => openPdf(q));
    list.appendChild(el);
  }
}
$('#search').addEventListener('input', drawList);

$('#btnCsv').addEventListener('click', () => {
  if (!listCache.length) { toast('Noch keine Quittungen vorhanden.'); return; }
  const esc = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
  const rows = [['Nr', 'Datum', 'Art', 'Betrag brutto', 'USt-Satz', 'Netto', 'USt', 'Zahler/Empfänger', 'Verwendungszweck', 'Zahlungsart', 'Dateiname']];
  const sorted = [...listCache].sort((a, b) => (a.datum + a.nr).localeCompare(b.datum + b.nr));
  for (const q of sorted) {
    const st = steuer(q.betrag, q.ust);
    rows.push([q.nr, dmy(q.datum), q.art === 'einnahme' ? 'Einnahme' : 'Ausgabe', fmt(q.betrag),
      q.ust === 'ku' ? '§19' : st.satz + ' %', fmt(st.netto), fmt(st.ust), q.partnerName, q.zweck, q.zahlungsart, q.dateiname]);
  }
  const csv = '﻿' + rows.map((r) => r.map(esc).join(';')).join('\r\n');
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  const a = document.createElement('a');
  a.href = url;
  a.download = `Quittungen_${todayIso()}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 60000);
});

/* ---------- Einstellungen ---------- */

async function fillSettingsForm() {
  const s = loadSettings();
  const fe = formS.elements;
  ['firma', 'strasse', 'plzOrt', 'steuernr', 'ustid', 'kontakt', 'ort', 'praefix'].forEach((k) => { fe[k].value = s[k] || ''; });
  fe.standardUst.value = s.standardUst || '19';
  fe.kleinunternehmer.checked = !!s.kleinunternehmer;
  const jahr = new Date().getFullYear();
  fe.naechste.value = await naechsteNummer(jahr);
  updateNumPreview();
  $('#settingsError').hidden = true;
}

function updateNumPreview() {
  const fe = formS.elements;
  const n = parseInt(fe.naechste.value, 10) || 1;
  $('#numPreview').textContent = formatNr(fe.praefix.value || 'Q', new Date().getFullYear(), n);
}
formS.elements.praefix.addEventListener('input', updateNumPreview);
formS.elements.naechste.addEventListener('input', updateNumPreview);

formS.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fe = formS.elements;
  const s = loadSettings();
  ['firma', 'strasse', 'plzOrt', 'steuernr', 'ustid', 'kontakt', 'ort'].forEach((k) => { s[k] = fe[k].value.trim(); });
  s.praefix = fe.praefix.value.trim().replace(/[^\w-]/g, '') || 'Q';
  s.standardUst = fe.standardUst.value;
  s.kleinunternehmer = fe.kleinunternehmer.checked;

  const missing = [];
  ['firma', 'strasse', 'plzOrt'].forEach((k) => {
    fe[k].classList.toggle('invalid', !s[k]);
    if (!s[k]) missing.push(fe[k].closest('.field').querySelector('span').textContent.replace(' *', ''));
  });
  if (missing.length) {
    showError($('#settingsError'), 'Bitte ausfüllen: ' + missing.join(', ') + '.');
    return;
  }

  const jahr = String(new Date().getFullYear());
  const n = parseInt(fe.naechste.value, 10);
  if (n > 0) s.zaehler[jahr] = n - 1;

  if (!saveSettings(s)) {
    showError($('#settingsError'), 'Speichern nicht möglich (privater Modus?).');
    return;
  }
  $('#settingsError').hidden = true;
  updateBrand();
  toast('Firmendaten gespeichert.');
  if (!lastCreated) await resetForm();
  showView('neu');
});

/* ---------- Start ---------- */

async function init() {
  updateBrand();
  await resetForm();
  if (!settingsComplete(loadSettings())) {
    showView('einstellungen');
    toast('Willkommen! Bitte zuerst die Firmendaten eintragen.', 4000);
  }
  // Speicher dauerhaft machen (Browser darf Daten dann nicht automatisch löschen)
  if (navigator.storage && navigator.storage.persist) {
    try {
      const ok = await navigator.storage.persist();
      $('#storageInfo').textContent = ok
        ? 'Speicher auf diesem Gerät ist dauerhaft gesichert.'
        : 'Tipp: App zum Startbildschirm hinzufügen, damit der Browser die Daten nicht löscht.';
    } catch (e) { /* egal */ }
  }
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
