const CACHE_NAME = 'shopbarber-v3'; // Bumped version to clear old cache
const urlsToCache = [
    '/',
    '/manifest.json'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
    );
    self.skipWaiting(); // Force immediate activation
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName); // Delete old caches
                    }
                })
            );
        })
    );
    self.clients.claim(); // Take control immediately
});

self.addEventListener('fetch', event => {
    // Don't cache PHP files or settings-related content
    if (event.request.url.includes('.php') || event.request.url.includes('settings')) {
        return; // Let it go to network
    }

    event.respondWith(
        caches.match(event.request).then(response => response || fetch(event.request))
    );
});
