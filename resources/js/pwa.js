// Installation PWA et mises à jour du service worker.
// Les événements sont relayés à la page (Alpine) : sysdental-installable, sysdental-mise-a-jour.

let invite = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    invite = event;
    window.dispatchEvent(new CustomEvent('sysdental-installable'));
});

window.addEventListener('appinstalled', () => {
    invite = null;
    window.dispatchEvent(new CustomEvent('sysdental-installee'));
});

window.sysdentalInstaller = async () => {
    if (!invite) {
        return false;
    }
    invite.prompt();
    const { outcome } = await invite.userChoice;
    invite = null;
    return outcome === 'accepted';
};

window.sysdentalEstIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent)
    && !window.matchMedia('(display-mode: standalone)').matches
    && !navigator.standalone;

let enAttente = null;

window.sysdentalMettreAJour = () => {
    if (enAttente) {
        enAttente.postMessage('activer');
    }
};

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', async () => {
        try {
            const enregistrement = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            const signaler = (worker) => {
                enAttente = worker;
                window.dispatchEvent(new CustomEvent('sysdental-mise-a-jour'));
            };
            if (enregistrement.waiting && navigator.serviceWorker.controller) {
                signaler(enregistrement.waiting);
            }
            enregistrement.addEventListener('updatefound', () => {
                const nouveau = enregistrement.installing;
                nouveau?.addEventListener('statechange', () => {
                    if (nouveau.state === 'installed' && navigator.serviceWorker.controller) {
                        signaler(nouveau);
                    }
                });
            });
        } catch (erreur) {
            console.warn('Service worker non enregistré', erreur);
        }
    });

    let rechargee = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (enAttente && !rechargee) {
            rechargee = true;
            window.location.reload();
        }
    });
}
