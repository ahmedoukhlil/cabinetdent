// Odontogramme anatomique (react-odontogram) monté dans les pages Livewire.
// Chargé à la demande : seules les pages qui affichent un odontogramme téléchargent React.
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { Odontogram } from 'react-odontogram';
import 'react-odontogram/style.css';

const racines = new WeakMap();
const vers = (numeros) => numeros.map((n) => `teeth-${n}`);
const depuis = (dents) => dents.map((d) => Number(d.notations?.fdi ?? String(d.id).replace('teeth-', ''))).filter(Boolean);

/**
 * @param {HTMLElement} element
 * @param {{ selection: number[], conditions: Array, lectureSeule?: boolean, surChangement?: (numeros: number[]) => void }} options
 */
export function monter(element, { selection = [], conditions = [], lectureSeule = false, surChangement }) {
    let etat = racines.get(element);
    if (!etat) {
        etat = { racine: createRoot(element), cle: 0, derniere: null };
        racines.set(element, etat);
    }
    // Le composant ne lit la sélection qu'au premier rendu : on le remonte quand elle change de l'extérieur.
    const signature = [...selection].sort().join(',');
    if (signature !== etat.derniere) {
        etat.cle++;
        etat.derniere = signature;
    }

    etat.racine.render(createElement(Odontogram, {
        key: etat.cle,
        defaultSelected: vers(selection),
        teethConditions: conditions.map((c) => ({ ...c, teeth: vers(c.teeth) })),
        readOnly: lectureSeule,
        notation: 'FDI',
        layout: 'circle',
        showLabels: true,
        colors: { darkBlue: '#0f4c81', baseBlue: '#94a3b8', lightBlue: '#7dd3fc' },
        tooltip: { placement: 'top', content: (d) => `Dent ${d?.notations?.fdi ?? ''}` },
        styles: { width: '100%', margin: '0 auto' },
        onChange: (dents) => {
            const numeros = depuis(dents);
            etat.derniere = [...numeros].sort().join(','); // changement interne : pas de remontage
            surChangement?.(numeros);
        },
    }));

    // Le composant tiers expose des libellés anglais et des groupes SVG non focalisables.
    // On les adapte pour la navigation clavier et les lecteurs d'écran de l'application.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        element.querySelectorAll('[aria-label^="Tooth "]').forEach((dent) => {
            dent.setAttribute('aria-label', dent.getAttribute('aria-label').replace('Tooth ', 'Dent '));
            if (lectureSeule) return;
            dent.setAttribute('role', 'button');
            dent.setAttribute('tabindex', '0');
            if (dent.dataset.clavierSysdental) return;
            dent.dataset.clavierSysdental = '1';
            dent.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    dent.dispatchEvent(new MouseEvent('click', { bubbles: true }));
                }
            });
        });
    }));
}

export function demonter(element) {
    racines.get(element)?.racine.unmount();
    racines.delete(element);
}
