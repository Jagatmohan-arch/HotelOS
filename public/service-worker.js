const CACHE_NAME = 'hotelos-v1';
const ASSETS = [
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/manifest.json'
];

// Install Event - Cache Static Assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

// Activate Event - Clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
});

// Fetch Event - Network First, fallback to cache for pages. Cache First for assets.
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Static Assets: Cache First, Network Fallback
    if (url.pathname.startsWith('/assets/') || url.pathname.includes('lucide')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                return cached || fetch(event.request).then((response) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, response.clone());
                        return response;
                    });
                });
            })
        );
        return;
    }

    // API & Pages: Network Only (Don't cache sensitive data)
    // We want real-time data for hotel ops
});
