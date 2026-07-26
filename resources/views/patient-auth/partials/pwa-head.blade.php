<link rel="manifest" href="/pwa/manifest.json">
<meta name="theme-color" content="#2563eb">
<link rel="apple-touch-icon" href="/pwa/icon-192.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/pwa/sw.js', { scope: '/espace-patient' })
                .catch((err) => console.error('Échec enregistrement service worker :', err));
        });
    }
</script>
