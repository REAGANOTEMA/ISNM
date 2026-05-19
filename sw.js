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
      // Return cached version if found
      if (cached) {
        return cached;
      }
      // Otherwise, try to fetch from network
      return fetch(event.request).catch(() => {
        // If network request fails, try to return a fallback offline page
        // For now, we'll just re-throw the error to let the browser handle it
        throw new Error('Network request failed and no cached version available');
      });
    })
  );
});
