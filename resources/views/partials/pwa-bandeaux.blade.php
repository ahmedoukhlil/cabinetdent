{{-- Bandeaux PWA : nouvelle version prête, installation (Chromium), aide iOS. --}}
<div x-data="{
        majPrete: false,
        installable: false,
        ios: false,
        visible: false,
        masque: localStorage.getItem('sysdental-install-masque') === '1',
        init() {
            this.ios = window.sysdentalEstIos?.() ?? false;
            const compteur = 'sysdental-visites';
            if (!sessionStorage.getItem('sysdental-visite-comptee')) {
                localStorage.setItem(compteur, String((Number(localStorage.getItem(compteur)) || 0) + 1));
                sessionStorage.setItem('sysdental-visite-comptee', '1');
            }
            this.visible = (Number(localStorage.getItem(compteur)) || 0) >= 2;
        },
        masquer() { this.masque = true; localStorage.setItem('sysdental-install-masque', '1') },
        async installer() { await window.sysdentalInstaller(); this.installable = false },
     }"
     x-on:sysdental-mise-a-jour.window="majPrete = true"
     x-on:sysdental-installable.window="installable = true"
     x-on:sysdental-installee.window="installable = false"
     class="fixed inset-x-0 bottom-20 z-40 flex flex-col items-center gap-2 px-4 md:bottom-4">
    <div x-show="majPrete" x-cloak role="status" class="flex w-full max-w-md items-center gap-3 rounded-lg bg-slate-900 px-4 py-3 text-sm text-white shadow-lg">
        <i class="ti ti-refresh" aria-hidden="true"></i>
        <span class="flex-1">Une nouvelle version de Sysdental est disponible.</span>
        <button type="button" x-on:click="window.sysdentalMettreAJour()" class="font-semibold underline">Mettre à jour</button>
    </div>
    <div x-show="visible && !masque && (installable || ios)" x-cloak class="flex w-full max-w-md items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm shadow-lg">
        <img src="/icons/icon-192.png" alt="" class="h-8 w-8">
        <span class="flex-1" x-show="installable">Installez Sysdental sur cet appareil pour l'ouvrir comme une application.</span>
        <span class="flex-1" x-show="!installable && ios">Pour installer Sysdental : touchez <i class="ti ti-share" aria-label="Partager"></i> puis « Sur l'écran d'accueil ».</span>
        <button type="button" x-show="installable" x-on:click="installer()" class="btn-primary">Installer</button>
        <button type="button" x-on:click="masquer()" class="text-slate-500 hover:text-slate-800" aria-label="Masquer"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
</div>
