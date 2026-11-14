/* Service Worker: macht die App offline nutzbar.
   Bei Änderungen an den Dateien CACHE-Version erhöhen. */
const CACHE = 'quittung-v1.1.0';
const FILES = [
  './',
  './index.html',
  './style.css',
  './app.js',
  './vendor/jspdf.umd.min.js',
  './manifest.webmanifest',
  './icons/icon-192.png',
  './icons/icon-512.png',
  './icons/apple-touch-icon.png'
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(FILES)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k.startsWith('quittung-') && k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// Online: immer aktuelle Version laden und Offline-Kopie auffrischen.
// Offline (oder Netz hängt > 4 s): Offline-Kopie verwenden.
// Leitet der Server um (z. B. zur Anmeldung, weil die Sitzung abgelaufen ist),
// wird die Umleitung angezeigt, aber nie in die Offline-Kopie übernommen.
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET' || new URL(e.request.url).origin !== location.origin) return;
  e.respondWith((async () => {
    const cache = await caches.open(CACHE);
    const fromCache = async () => {
      const hit = await cache.match(e.request, { ignoreSearch: true });
      if (hit || e.request.mode !== 'navigate') return hit;
      return (await cache.match('./')) || cache.match('./index.html');
    };
    try {
      const res = await Promise.race([
        fetch(e.request, { cache: 'no-cache' }),
        new Promise((_, rej) => setTimeout(() => rej(new Error('timeout')), 4000))
      ]);
      if (res && res.ok && !res.redirected) cache.put(e.request, res.clone());
      return res;
    } catch (err) {
      const cached = await fromCache();
      if (cached) return cached;
      throw err;
    }
  })());
});
