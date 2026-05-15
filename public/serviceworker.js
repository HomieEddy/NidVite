var staticCacheName = "pwa-v" + new Date().getTime();
var offlineUrl = '/offline';
var requiredAssets = [
    offlineUrl,
];
var optionalAssets = [
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png',
];

// Cache on install
self.addEventListener("install", event => {
    this.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName)
            .then(cache => {
                var requiredPromises = requiredAssets.map(url =>
                    fetch(url)
                        .then(response => {
                            if (response.ok) {
                                return cache.put(url, response);
                            }
                        })
                        .catch(error => {
                            console.warn('[SW] Failed to cache required asset:', url, error);
                        })
                );

                var optionalPromises = optionalAssets.map(url =>
                    fetch(url)
                        .then(response => {
                            if (response.ok) {
                                return cache.put(url, response);
                            }
                        })
                        .catch(() => {
                            // Silently skip optional assets.
                        })
                );

                return Promise.all(requiredPromises).then(() => Promise.all(optionalPromises));
            })
    );
});

// Clear cache on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith("pwa-")))
                    .filter(cacheName => (cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        })
    );
});

// Serve from Cache
self.addEventListener("fetch", event => {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request);
            })
            .catch(() => {
                return caches.match(offlineUrl).then(response => {
                    return response || Response.error();
                });
            })
    )
});
