const CACHE = 'isnm-v2';
const ASSETS = [
  '/',
  '/index.php'
];

self.addEventListener('install', e => {
  self.skipWaiting();
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(ASSETS))
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(k => Promise.all(k.filter(n => n !== CACHE).map(n => caches.delete(n))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request).then(res => {
      return caches.open(CACHE).then(c => { c.put(e.request, res.clone()); return res; });
    }))
  );
});

self.addEventListener('push', e => {
  if (!e.data) return;
  let data;
  try { data = e.data.json(); } catch { data = { title: 'ISNM Notification', body: e.data.text() }; }
  const opts = {
    body: data.body || '',
    icon: data.icon || '/images/school-logo.png',
    badge: '/images/school-logo.png',
    data: { url: data.url || '/' },
    vibrate: [200, 100, 200],
    tag: 'isnm-push-' + Date.now(),
    requireInteraction: true
  };
  e.waitUntil(
    self.registration.showNotification(data.title || 'ISNM', opts)
  );
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = e.notification.data?.url || '/';
  e.waitUntil(
    clients.matchAll({ type: 'window' }).then(cls => {
      for (const c of cls) { if (c.url === url && 'focus' in c) return c.focus(); }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
