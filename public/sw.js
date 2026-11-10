/*
 * Service worker Sysdental.
 * Ne met en cache que la coque statique (assets compilés, icônes, page hors ligne).
 * Pages, réponses Livewire, fichiers patients, factures : toujours réseau, jamais en cache,
 * pour ne jamais exposer de données médicales hors connexion.
 */
const VERSION = 'sysdental-v1';
const COQUE = ['/offline.html', '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(COQUE)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cles) => Promise.all(cles.filter((c) => c !== VERSION).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

// Activation contrôlée : la page demande l'activation quand l'utilisateur clique « Mettre à jour ».
self.addEventListener('message', (event) => {
    if (event.data === 'activer') {
        self.skipWaiting();
    }
});

const estStatique = (url) => url.origin === self.location.origin
    && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/'));

self.addEventListener('fetch', (event) => {
    const requete = event.request;
    if (requete.method !== 'GET') {
        return;
    }
    const url = new URL(requete.url);

    // Navigation : réseau uniquement, page hors ligne en secours.
    if (requete.mode === 'navigate') {
        event.respondWith(fetch(requete).catch(() => caches.match('/offline.html')));
        return;
    }

    // Assets versionnés par Vite : cache d'abord.
    if (estStatique(url)) {
        event.respondWith(
            caches.match(requete).then((enCache) => enCache || fetch(requete).then((reponse) => {
                if (reponse.ok) {
                    const copie = reponse.clone();
                    caches.open(VERSION).then((cache) => cache.put(requete, copie));
                }
                return reponse;
            }))
        );
    }
    // Tout le reste passe par le réseau sans être intercepté.
});
