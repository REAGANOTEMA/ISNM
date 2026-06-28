/**
 * ISNM Service Worker
 * Production-ready PWA support for Iganga School of Nursing & Midwifery
 */

const CACHE_VERSION = 'v1';
const CACHE_NAME = `isnm-${CACHE_VERSION}`;
const STATIC_CACHE = `isnm-static-${CACHE_VERSION}`;
const OFFLINE_CACHE = `isnm-offline-${CACHE_VERSION}`;
const OFFLINE_PAGE = '/offline.html';

const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/css/isnm-style.css',
  '/css/modern-ui.css',
  '/css/header.css',
  '/css/responsive.css',
  '/css/animations.css',
  '/css/bootstrap.min.css',
  '/js/bootstrap.bundle.min.js',
  '/js/bootstrap.min.js',
  '/images/school-logo.png',
  '/offline.html'
];

const PRECACHE_ROUTES = new Set(STATIC_ASSETS);

// Install: cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    Promise.all([
      caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)),
      caches.open(OFFLINE_CACHE).then((cache) => cache.add(OFFLINE_PAGE))
    ]).then(() => self.skipWaiting())
  );
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
  const currentCaches = [STATIC_CACHE, OFFLINE_CACHE, CACHE_NAME];
  event.waitUntil(
    caches.keys()
      .then((cacheNames) =>
        Promise.all(
          cacheNames
            .filter((name) => !currentCaches.includes(name))
            .map((name) => caches.delete(name))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Fetch strategies
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') return;

  if (url.origin !== self.location.origin) return;

  if (request.headers.get('range')) return;

  const pathname = url.pathname;

  if (pathname.endsWith('.php') || pathname === '/' || pathname.endsWith('/')) {
    event.respondWith(networkFirst(request));
    return;
  }

  if (/\.(css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|eot|webp|avif)$/i.test(pathname)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  event.respondWith(networkFirst(request));
});

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await caches.match(request);
    if (cached) return cached;

    if (request.mode === 'navigate') {
      const offlinePage = await caches.match(OFFLINE_PAGE);
      if (offlinePage) return offlinePage;
    }

    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

// Push Notifications
self.addEventListener('push', (event) => {
  let data = {
    title: 'ISNM Update',
    body: 'You have a new notification.',
    icon: '/images/school-logo.png',
    url: '/'
  };

  if (event.data) {
    try {
      const parsed = event.data.json();
      data = { ...data, ...parsed };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: '/images/school-logo.png',
    vibrate: [200, 100, 200],
    data: { url: data.url },
    tag: 'isnm-notification',
    renotify: true
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.focus();
          if (url !== '/') client.navigate(url);
          return;
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});

self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    self.registration.pushManager.subscribe(event.oldSubscription.options)
      .then((subscription) => {
        console.log('[SW] Push subscription renewed');
        return subscription;
      })
  );
});
