// Recopie le libellé de l'en-tête de colonne sur chaque cellule des tableaux .tableau-cartes
// (attribut data-label), pour leur affichage en fiches sur mobile. Réappliqué après
// chaque mise à jour Livewire grâce à un MutationObserver.
function etiqueter(racine = document) {
    racine.querySelectorAll('table.tableau-cartes').forEach((table) => {
        const entetes = [...table.querySelectorAll('thead tr:last-child th')].map((th) => th.textContent.trim());
        if (!entetes.length) return;
        table.querySelectorAll('tbody tr, tfoot tr').forEach((tr) => {
            let col = 0;
            [...tr.children].forEach((cellule) => {
                const libelle = entetes[col] ?? '';
                if (cellule.getAttribute('data-label') !== libelle) cellule.setAttribute('data-label', libelle);
                col += Number(cellule.getAttribute('colspan')) || 1;
            });
        });
    });
}

let prevu = false;
const planifier = () => {
    if (prevu) return;
    prevu = true;
    requestAnimationFrame(() => { prevu = false; etiqueter(); });
};

document.addEventListener('DOMContentLoaded', () => {
    etiqueter();
    new MutationObserver(planifier).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-label'] });
});
document.addEventListener('livewire:navigated', planifier);
