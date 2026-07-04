/**
 * ISNM Progressive Web App Service Worker
 * Handles offline functionality, caching, and push notifications
 */

const CACHE_VERSION = 'isnm-v3';
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/css/responsive.css',
  '/css/style.css',
  '/js/app.js',
  '/offline.html',
  '/manifest.json',
];

const DYNAMIC_CACHE = 'isnm-dynamic-v3';
const API_CACHE = 'isnm-api-v3';

// Install Event - Cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  self.skipWaiting();
  
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) => {
      console.log('Service Worker: Caching static assets');
      return cache.addAll(STATIC_ASSETS);
    })
  );
});

// Activate Event - Clean old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_VERSION && cacheName !== DYNAMIC_CACHE && cacheName !== API_CACHE) {
            console.log('Service Worker: Deleting old cache -', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  
  self.clients.claim();
});

// Fetch Event - Network first with fallback to cache
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // API requests - Network first with API cache fallback
  if (url.pathname.includes('/includes/') || url.pathname.includes('?action=')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Clone and cache successful responses
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(API_CACHE).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return response;
        })
        .catch(() => {
          // Fallback to cached response
          return caches.match(request).then((cached) => {
            return cached || new Response('Offline - API not available', {
              status: 503,
              statusText: 'Service Unavailable',
            });
          });
        })
    );
    return;
  }

  // Static assets - Cache first with network fallback
  if (request.url.includes('.css') || request.url.includes('.js') || request.url.includes('.png') || request.url.includes('.jpg')) {
    event.respondWith(
      caches.match(request).then((cached) => {
        return cached || fetch(request).then((response) => {
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return response;
        }).catch(() => {
          // Serve offline page if available
          if (request.url.includes('.html')) {
            return caches.match('/offline.html');
          }
        });
      })
    );
    return;
  }

  // HTML pages - Network first with cache fallback
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response && response.status === 200) {
          const responseToCache = response.clone();
          caches.open(DYNAMIC_CACHE).then((cache) => {
            cache.put(request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(request).then((cached) => {
          return cached || caches.match('/offline.html').then((offline) => {
            return offline || new Response('Offline', { status: 503 });
          });
        });
      })
  );
});

// Background Sync for form submissions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-forms') {
    event.waitUntil(
      // Sync pending form submissions
      caches.open(API_CACHE).then((cache) => {
        return cache.keys().then((keys) => {
          return Promise.all(
            keys
              .filter(request => request.url.includes('?action=submit_form'))
              .map(request => fetch(request))
          );
        });
      })
    );
  }
});

// Push Notification Event
self.addEventListener('push', (event) => {
  if (!event.data) {
    return;
  }

  let notificationData = {};

  try {
    notificationData = event.data.json();
  } catch {
    notificationData = {
      title: 'ISNM Notification',
      body: event.data.text(),
    };
  }

  const options = {
    body: notificationData.body || '',
    icon: notificationData.icon || '/images/school-logo.png',
    badge: '/images/school-logo-badge.png',
    tag: 'isnm-notification',
    requireInteraction: true,
    vibrate: [200, 100, 200],
    data: {
      url: notificationData.url || '/',
      timestamp: Date.now(),
    },
    actions: [
      {
        action: 'open',
        title: 'Open',
        icon: '/images/check-icon.png',
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/images/close-icon.png',
      },
    ],
  };

  event.waitUntil(
    self.registration.showNotification(notificationData.title || 'ISNM', options)
  );
});

// Notification Click Event
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';

  if (event.action === 'close') {
    return;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      // Check if window already open
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      // Open new window if not found
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});

// Message handling for client-side requests
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.delete(DYNAMIC_CACHE);
    caches.delete(API_CACHE);
  }
});
  e.waitUntil(
    clients.matchAll({ type: 'window' }).then(cls => {
      for (const c of cls) { if (c.url === url && 'focus' in c) return c.focus(); }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
