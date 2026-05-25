const CACHE_NAME = 'isnm-static-v1';
const ASSETS = [
  './',
  './index.php',
  './organogram.php',
  './staff-login.php',
  './student-login.php',
  './images/school-logo.png',
  './manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) {
        return cached;
      }
      return fetch(event.request).catch(() => {
        // If the request is for a document (navigation), try to return the shell
        if (event.request.destination === 'document') {
          return caches.match('./');
        }
        // Otherwise, return an error response
        return new Response('Network request failed and no cached version available.', { status: 503, statusText: 'Service Unavailable' });
      });
    })
  );
});