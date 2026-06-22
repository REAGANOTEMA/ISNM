// ISNM Service Worker — passthrough only, no caching of PHP or navigation
const CACHE_NAME = 'isnm-static-v4';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('unhandledrejection', (e) => {
  if (e.preventDefault) e.preventDefault();
});

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

  const isImmutable = /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/i.test(url.pathname);
  if (!isImmutable) return;

  event.respondWith(
    caches.open(CACHE_NAME).then((cache) =>
      cache.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((response) => {
          if (response.ok) {
            cache.put(event.request, response.clone()).catch(() => {});
          }
          return response;
        });
      }).catch(() => fetch(event.request))
    ).catch(() => fetch(event.request))
  );
});

// ── Push Notifications ──
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
  } catch (e) { /* use defaults */ }

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
