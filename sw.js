// Service Worker for Answer Vault PWA - SMART CACHE (Speed + Accuracy)
const CACHE_NAME = 'answer-vault-v2';

// Install event - activate immediately
self.addEventListener('install', event => {
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - NETWORK FIRST (Absolute Accuracy)
// Always tries network, uses cache only if offline.
// If online, it updates the cache silently for next time.
self.addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                // If network works, put a clone in the cache and return it
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // If network fails (offline), try the cache
                return caches.match(event.request);
            })
    );
});
