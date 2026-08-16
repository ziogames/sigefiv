const CACHE_NAME = 'sigefiv-v1';

self.addEventListener('install', event => {
    console.log('[SIGEFIV] Service Worker instalado');

    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('[SIGEFIV] Service Worker activo');

    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    // Por ahora dejamos las peticiones pasar directamente a Laravel.
    // No cacheamos datos financieros ni páginas dinámicas.
});