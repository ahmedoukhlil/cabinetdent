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
export function monter(element, { selection = [], conditions = [], lectureSeule = false, numeros = false, surChangement }) {
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
        if (numeros) afficherNumeros(element);
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

/**
 * Écrit le numéro FDI de chaque dent à l'extérieur de l'arcade (documents imprimés).
 * Le composant tiers n'affiche pas les numéros : on les calcule depuis la position de chaque dent.
 */
function afficherNumeros(element) {
    const svg = element.querySelector('svg');
    if (!svg || svg.querySelector('[data-numeros-fdi]')) return;
    const boites = [...svg.querySelectorAll('g[class^="teeth-"]')]
        .map((dent) => ({ numero: (dent.getAttribute('class').match(/teeth-(\d+)/) || [])[1], b: boiteDansSvg(svg, dent) }))
        .filter(({ numero }) => numero);
    if (!boites.length) return;

    const xs = boites.flatMap(({ b }) => [b.x, b.x + b.width]);
    const ys = boites.flatMap(({ b }) => [b.y, b.y + b.height]);
    const cx = (Math.min(...xs) + Math.max(...xs)) / 2;
    const cy = (Math.min(...ys) + Math.max(...ys)) / 2;
    const taille = Math.max(...boites.map(({ b }) => Math.max(b.width, b.height)));

    const ns = 'http://www.w3.org/2000/svg';
    const groupe = document.createElementNS(ns, 'g');
    groupe.setAttribute('data-numeros-fdi', '');
    groupe.setAttribute('aria-hidden', 'true');
    boites.forEach(({ numero, b }) => {
        const x = b.x + b.width / 2;
        const y = b.y + b.height / 2;
        const dx = x - cx;
        const dy = y - cy;
        const d = Math.hypot(dx, dy) || 1;
        const ecart = taille * 0.75;
        const texte = document.createElementNS(ns, 'text');
        texte.setAttribute('x', x + (dx / d) * ecart);
        texte.setAttribute('y', y + (dy / d) * ecart);
        texte.setAttribute('text-anchor', 'middle');
        texte.setAttribute('dominant-baseline', 'central');
        texte.setAttribute('font-size', taille * 0.42);
        texte.setAttribute('font-weight', '600');
        texte.setAttribute('fill', '#334155');
        texte.textContent = numero;
        groupe.appendChild(texte);
    });
    svg.appendChild(groupe);

    // Agrandit la zone visible pour que les numéros extérieurs ne soient pas coupés.
    const vb = svg.viewBox.baseVal;
    if (vb && vb.width) {
        const marge = taille * 1.3;
        svg.setAttribute('viewBox', `${vb.x - marge} ${vb.y - marge} ${vb.width + 2 * marge} ${vb.height + 2 * marge}`);
    }
}

/** Boîte englobante d'un élément dans le repère du SVG racine (tient compte des transform imbriqués). */
function boiteDansSvg(svg, el) {
    const r = el.getBoundingClientRect();
    const m = svg.getScreenCTM().inverse();
    const point = (x, y) => new DOMPoint(x, y).matrixTransform(m);
    const a = point(r.left, r.top);
    const b = point(r.right, r.bottom);

    return { x: a.x, y: a.y, width: b.x - a.x, height: b.y - a.y };
}
