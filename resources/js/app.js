import './bootstrap';
import './pwa';
import './confirmation';

// Odontogramme anatomique : composant Alpine qui charge React à la demande.
// Les données arrivent par l'attribut data-odontogramme d'un élément voisin, mis à jour par Livewire.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('odontogramme', (lectureSeule = false) => ({
        init() {
            const source = this.$el.querySelector('[data-odontogramme]');
            const scene = this.$el.querySelector('[data-scene]');
            const dessiner = () => {
                const donnees = JSON.parse(source.dataset.odontogramme);
                import('./odontogramme').then((m) => m.monter(scene, {
                    ...donnees,
                    lectureSeule,
                    surChangement: (numeros) => this.$wire.definirSelection(numeros),
                }));
            };
            dessiner();
            this.observateur = new MutationObserver(dessiner);
            this.observateur.observe(source, { attributes: true });
        },
        destroy() {
            this.observateur?.disconnect();
            import('./odontogramme').then((m) => m.demonter(this.$el.querySelector('[data-scene]')));
        },
    }));
});
