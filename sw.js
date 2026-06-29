/* ISNM Service Worker - Single consolidated version */
const CACHE_NAME = 'isnm-static-v5';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
      .catch(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (
    event.request.method !== 'GET' ||
    url.pathname.endsWith('.php') ||
    url.origin !== self.location.origin
  ) {
    return;
  }

  if (
    url.pathname.startsWith('/writing/') ||
    url.pathname.startsWith('/generate/') ||
    url.pathname.startsWith('/site_integration/')
  ) {
    return;
  }

  const isImmutable = /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/i.test(url.pathname);
  if (!isImmutable) return;

  event.respondWith(
    caches.open(CACHE_NAME).then((cache) =>
      cache.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((response) => {
          if (response.ok) {
            cache.put(event.request, response.clone()).catch(() => { console.warn('[SW] Cache put failed (non-critical)'); });
          }
          return response;
        });
      }).catch(() => fetch(event.request))
    ).catch(() => fetch(event.request))
  );
});

self.addEventListener('push', (event) => {
  let data = { title: 'ISNM Update', body: 'You have a new notification.', icon: '/favicon.ico', url: '/' };
  try {
    if (event.data) {
      const parsed = event.data.json();
      if (parsed.title) data.title = parsed.title;
      if (parsed.body) data.body = parsed.body;
      if (parsed.icon) data.icon = parsed.icon;
      if (parsed.url) data.url = parsed.url;
    }
  } catch (e) {}

  const options = {
    body: data.body,
    icon: data.icon,
    badge: '/favicon.ico',
    vibrate: [200, 100, 200],
    data: { url: data.url }
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      for (const w of windows) {
        if (w.url.startsWith(self.location.origin) && !w.url.includes('/logout')) {
          w.focus();
          if (url !== '/') w.navigate(url);
          return;
        }
      }
      clients.openWindow(url);
    })
  );
});
