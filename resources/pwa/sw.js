// Service Worker — Espace Patient SysMedical
// Cache uniquement les ressources statiques (icônes, manifest). Les pages
// de données (plan de traitement, factures, paiements) ne sont JAMAIS mises
// en cache : elles contiennent des informations médicales/financières qui
// doivent toujours refléter l'état réel du serveur, pas une copie locale
// potentiellement obsolète ou visible par un tiers ayant accès à l'appareil.
const CACHE_NAME = 'sysmedical-patient-v1';
const RESSOURCES_STATIQUES = [
    '/pwa/manifest.json',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(RESSOURCES_STATIQUES))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((noms) =>
            Promise.all(noms.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Seules les ressources statiques du dossier /pwa/ passent par le cache
    // (cache-first). Tout le reste (pages, API) va systématiquement au
    // réseau — aucune donnée patient n'est mise en cache local.
    if (url.pathname.startsWith('/pwa/')) {
        event.respondWith(
            caches.match(event.request).then((reponse) => reponse || fetch(event.request))
        );
    }
});

self.addEventListener('push', (event) => {
    if (!event.data) return;
    const donnees = event.data.json();
    event.waitUntil(
        self.registration.showNotification(donnees.title || 'SysMedical', {
            body: donnees.body || '',
            icon: '/pwa/icon-192.png',
            badge: '/pwa/icon-192.png',
            data: { url: donnees.url || '/espace-patient' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/espace-patient';
    event.waitUntil(clients.openWindow(url));
});
