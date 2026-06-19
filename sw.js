// ISNM Service Worker — passthrough only, no caching of PHP or navigation
const CACHE_NAME = 'isnm-static-v3';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Pass through everything — no caching at all for this app
  // PHP pages, dashboards, auth, and navigation must never be cached
  if (
    event.request.method !== 'GET' ||
    url.pathname.endsWith('.php') ||
    url.origin !== self.location.origin
  ) {
    return;
  }

  // Only cache truly static immutable assets (images/fonts)
  const isImmutable = /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/i.test(url.pathname);
  if (!isImmutable) return;

  event.respondWith(
    caches.open(CACHE_NAME).then((cache) =>
      cache.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((response) => {
          if (response.ok) cache.put(event.request, response.clone());
          return response;
        });
      })
    )
  );
});
