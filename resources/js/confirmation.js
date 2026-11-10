// Remplace la boîte native du navigateur (wire:confirm) par la fenêtre de confirmation de Sysdental.
// On intercepte le clic avant Livewire ; après accord, on rejoue le clic en laissant passer
// la confirmation native, que Livewire appelle de façon synchrone.
document.addEventListener('click', (event) => {
    const element = event.target.closest('[wire\\:confirm]');
    if (!element || element.dataset.confirme === '1') {
        return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();

    const message = element.getAttribute('wire:confirm') || 'Confirmer cette action ?';
    window.dispatchEvent(new CustomEvent('confirmer', {
        detail: {
            message,
            libelle: element.innerText.trim() || 'Confirmer',
            danger: /supprim|annul|désactiv|retir|rembours/i.test(message + ' ' + element.innerText),
            valider: () => {
                const confirmNatif = window.confirm;
                window.confirm = () => true;
                element.dataset.confirme = '1';
                try {
                    element.click();
                } finally {
                    delete element.dataset.confirme;
                    window.confirm = confirmNatif;
                }
            },
        },
    }));
}, true);
