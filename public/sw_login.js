const LOGIN_CACHE = 'app-login-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names.filter((n) => n.startsWith('app-login-') && n !== LOGIN_CACHE)
                     .map((n) => caches.delete(n))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});
