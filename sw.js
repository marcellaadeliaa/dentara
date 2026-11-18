/**
 * DENTARA PWA SERVICE WORKER
 * File: sw.js
 */

const CACHE_NAME = 'dentara-static-v1';
const STATIC_ASSETS = [
  'assets/css/design_tokens.css',
  'assets/css/components.css',
  'assets/css/modern_ui.css',
  'assets/css/landing.css',
  'assets/css/admin_theme.css',
  'assets/css/pasien_theme.css',
  'assets/css/gamifikasi.css',
  'assets/css/odontogram.css',
  'assets/css/tv.css',
  'assets/js/global.js',
  'assets/img/favicon.svg',
  'offline.html'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Hanya proses GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Jangan cache endpoint API dinamis atau transaksi
  if (url.pathname.includes('/api/') || url.pathname.includes('/process/')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Cache copy dari asset statis
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Fallback ke cache jika offline
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Jika request halaman HTML, berikan offline.html
          if (event.request.headers.get('accept').includes('text/html')) {
            return caches.match('offline.html');
          }
        });
      })
  );
});

