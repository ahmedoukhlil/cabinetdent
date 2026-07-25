import { createRoot } from 'react-dom/client';
import { useEffect, useRef } from 'react';
import { Odontogram } from 'react-odontogram';
import 'react-odontogram/style.css';

// Positions absentes chez l'enfant (dentition de lait) : seules les 5 premières
// positions existent — incisive centrale (1), incisive latérale (2), canine
// (3), 1re molaire de lait (4), 2e molaire de lait (5). Les positions 6/7/8
// (1re/2e/3e molaire permanentes) n'ont pas d'équivalent en denture primaire.
const POSITIONS_ABSENTES_EN_LAIT = [6, 7, 8];
const FDI_ADULTE = [11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 23, 24, 25, 26, 27, 28, 31, 32, 33, 34, 35, 36, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48];
const FDI_ABSENTS_EN_LAIT = FDI_ADULTE.filter((fdi) => POSITIONS_ABSENTES_EN_LAIT.includes(fdi % 10));

// Notation FDI primaire (dents de lait) : quadrants 5-8 au lieu de 1-4.
const QUADRANT_ADULTE_VERS_LAIT = { 1: 5, 2: 6, 3: 7, 4: 8 };
function fdiLaitDepuisAdulte(fdiAdulte) {
    const quadrant = Math.floor(fdiAdulte / 10);
    const position = fdiAdulte % 10;
    const quadrantLait = QUADRANT_ADULTE_VERS_LAIT[quadrant];
    return quadrantLait * 10 + position;
}

const STATUT_COLORS = {
    termine: { fill: '#bbf7d0', stroke: '#16a34a' },
    en_cours: { fill: '#fed7aa', stroke: '#ea580c' },
    planifie: { fill: '#e5e7eb', stroke: '#6b7280' },
    default: { fill: '#ffffff', stroke: '#9ca3af' },
};

const TYPE_TRADUCTIONS = {
    'Central Incisor': 'Incisive centrale',
    'Lateral Incisor': 'Incisive latérale',
    Canine: 'Canine',
    'First Premolar': 'Première prémolaire',
    'Second Premolar': 'Deuxième prémolaire',
    'First Molar': 'Première molaire',
    'Second Molar': 'Deuxième molaire',
    'Third Molar': 'Troisième molaire (dent de sagesse)',
};

function traduireType(type) {
    return TYPE_TRADUCTIONS[type] || type;
}

function toothIdFromFdi(fdi) {
    return `teeth-${fdi}`;
}

const QUADRANT_LAIT_VERS_ADULTE = { 5: 1, 6: 2, 7: 3, 8: 4 };
function fdiAdulteDepuisLait(fdiLait) {
    const quadrant = Math.floor(fdiLait / 10);
    const position = fdiLait % 10;
    const quadrantAdulte = QUADRANT_LAIT_VERS_ADULTE[quadrant];
    return quadrantAdulte ? quadrantAdulte * 10 + position : fdiLait;
}

function buildTeethConditions(conditionsParDent) {
    // Les dents "terminé" ne sont volontairement pas colorées : une fois le
    // traitement achevé, la dent redevient visuellement neutre sur le
    // schéma (seuls "planifié" et "en_cours" restent mis en évidence).
    const groups = { en_cours: [], planifie: [] };

    Object.entries(conditionsParDent || {}).forEach(([numDent, statut]) => {
        if (groups[statut]) {
            const fdiInterne = Number(numDent) >= 50 ? fdiAdulteDepuisLait(Number(numDent)) : numDent;
            groups[statut].push(toothIdFromFdi(fdiInterne));
        }
    });

    return Object.entries(groups)
        .filter(([, teeth]) => teeth.length > 0)
        .map(([statut, teeth]) => ({
            label: statut,
            teeth,
            fillColor: STATUT_COLORS[statut].fill,
            outlineColor: STATUT_COLORS[statut].stroke,
        }));
}

// Le wire:id passé au montage peut devenir obsolète si Livewire remonte le
// composant (nouvel id) sans jamais retoucher notre <div data-odontogram-root>
// (il vit sous wire:ignore). On relit toujours le wire:id réel et courant du
// composant Livewire ancêtre au moment de l'action, plutôt que de se fier à
// une valeur figée dans la closure React.
function resoudreWireIdCourant(container, wireIdSecours) {
    const hote = container?.closest('[wire\\:id]');
    return hote?.getAttribute('wire:id') || wireIdSecours;
}

function OdontogramReactif({ wireId, mode, conditionsParDent, modeMultiSelection, dentsSelectionnees }) {
    const containerRef = useRef(null);
    const estModeLait = mode === 'lait';

    // Échange la position gauche/droite des quadrants inférieurs (38↔48, 37↔47,
    // etc.) pour correspondre à la convention dentaire standard vue miroir :
    // la lib place nativement 3x à gauche-écran et 4x à droite-écran, alors que
    // la référence attendue veut 4x à gauche-écran et 3x à droite-écran.
    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;
        const svg = container.querySelector('svg.Odontogram');
        if (!svg) return;

        const g3 = svg.querySelector('.teeth-31')?.closest('g[transform]');
        const g4 = svg.querySelector('.teeth-41')?.closest('g[transform]');
        if (!g3 || !g4) return;

        // viewBox circle = "0 0 409 694" ; on reflète chaque quadrant du bas
        // autour de l'axe vertical central (x=204.5) en composant avec son
        // transform d'origine.
        const mirror = 'translate(409, 0) scale(-1, 1) ';
        if (!g3.dataset.mirrored) {
            g3.setAttribute('transform', mirror + g3.getAttribute('transform'));
            g3.dataset.mirrored = 'true';
        }
        if (!g4.dataset.mirrored) {
            g4.setAttribute('transform', mirror + g4.getAttribute('transform'));
            g4.dataset.mirrored = 'true';
        }
    });

    // Grise et désactive les dents absentes en denture primaire (prémolaires,
    // dent de sagesse) en manipulant directement les <g class="teeth-XX"> rendus
    // par la librairie, qui n'expose pas de prop readOnly par dent.
    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        FDI_ABSENTS_EN_LAIT.forEach((fdi) => {
            const el = container.querySelector(`.teeth-${fdi}`);
            if (!el) return;
            if (estModeLait) {
                el.style.opacity = '0.25';
                el.style.pointerEvents = 'none';
                el.setAttribute('aria-disabled', 'true');
            } else {
                el.style.opacity = '';
                el.style.pointerEvents = '';
                el.removeAttribute('aria-disabled');
            }
        });
    });

    // En mode multi-sélection, surligne les dents cochées (bordure bleue en
    // plus des couleurs de statut habituelles) via une classe CSS dédiée.
    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;
        const svg = container.querySelector('svg.Odontogram');
        if (!svg) return;

        const selectionneesInternes = new Set(
            (dentsSelectionnees || []).map((numDent) => {
                const n = Number(numDent);
                return n >= 50 ? fdiAdulteDepuisLait(n) : n;
            })
        );

        FDI_ADULTE.forEach((fdi) => {
            const g = svg.querySelector(`.teeth-${fdi}`);
            if (!g) return;
            if (selectionneesInternes.has(fdi)) {
                g.style.filter = 'drop-shadow(0 0 0 2px #3b82f6)';
            } else {
                g.style.filter = '';
            }
        });
    });

    // Affiche le numéro FDI de chaque dent : la librairie ne rend aucun texte
    // visible par défaut, on ajoute un <text> centré sur la bounding box de
    // chaque <g class="teeth-XX">.
    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        const svg = container.querySelector('svg.Odontogram');
        if (!svg) return;

        FDI_ADULTE.forEach((fdi) => {
            const g = svg.querySelector(`.teeth-${fdi}`);
            if (!g) return;

            let label = g.querySelector('text.odon-fdi-label');
            const dejaCree = !!label;
            if (!dejaCree) {
                const bbox = g.getBBox();
                const cx = bbox.x + bbox.width / 2;
                const cy = bbox.y + bbox.height / 2;

                // Lit le vrai scale appliqué par le <g> du quadrant parent (via sa
                // matrice CTM relative au SVG racine) pour contre-transformer le
                // libellé exactement, sans deviner le signe par quadrant FDI.
                const svgRoot = svg;
                const ctm = g.getCTM();
                const rootCtm = svgRoot.getCTM ? svgRoot.getCTM() : null;
                let flipX = 1;
                let flipY = 1;
                if (ctm && rootCtm) {
                    const relA = ctm.a / rootCtm.a;
                    const relD = ctm.d / rootCtm.d;
                    flipX = relA < 0 ? -1 : 1;
                    flipY = relD < 0 ? -1 : 1;
                }

                label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                label.setAttribute('class', 'odon-fdi-label');
                label.setAttribute('text-anchor', 'middle');
                label.setAttribute('dominant-baseline', 'middle');
                label.setAttribute('font-size', '20');
                label.setAttribute('font-weight', '700');
                label.setAttribute('fill', '#1f2937');
                label.setAttribute('stroke', '#ffffff');
                label.setAttribute('stroke-width', '3');
                label.setAttribute('paint-order', 'stroke');
                label.setAttribute('pointer-events', 'none');
                label.setAttribute(
                    'transform',
                    `translate(${cx} ${cy}) scale(${flipX} ${flipY}) translate(${-cx} ${-cy})`
                );
                label.setAttribute('x', String(cx));
                label.setAttribute('y', String(cy));
                g.appendChild(label);
            }
            if (estModeLait && FDI_ABSENTS_EN_LAIT.includes(fdi)) {
                label.textContent = '';
            } else {
                label.textContent = estModeLait ? String(fdiLaitDepuisAdulte(fdi)) : String(fdi);
            }
        });
    });

    return (
        <div ref={containerRef}>
            <Odontogram
                notation="FDI"
                layout="circle"
                showHalf="full"
                singleSelect={!modeMultiSelection}
                teethConditions={buildTeethConditions(conditionsParDent)}
                tooltip={{
                    placement: 'top',
                    content: (payload) =>
                        payload && (
                            <div>
                                <div>
                                    Dent :{' '}
                                    {estModeLait
                                        ? fdiLaitDepuisAdulte(Number(payload.notations.fdi))
                                        : payload.notations.fdi}
                                </div>
                                <div>Type : {traduireType(payload.type)}</div>
                            </div>
                        ),
                }}
                onChange={(selected) => {
                    if (!window.Livewire) return;
                    const idCourant = resoudreWireIdCourant(containerRef.current, wireId);

                    // En mode multi-sélection, la librairie renvoie le tableau
                    // complet des dents actuellement cochées à chaque clic (pas
                    // seulement la dernière) — on synchronise donc la liste
                    // entière côté serveur plutôt que de toggler une seule
                    // dent, pour rester fidèle à l'état interne de la lib.
                    if (modeMultiSelection) {
                        const dents = selected
                            .map((tooth) => Number(tooth.notations.fdi))
                            .filter((fdiAdulte) => !(estModeLait && FDI_ABSENTS_EN_LAIT.includes(fdiAdulte)))
                            .map((fdiAdulte) => (estModeLait ? String(fdiLaitDepuisAdulte(fdiAdulte)) : String(fdiAdulte)));
                        try {
                            window.Livewire.find(idCourant)?.call('definirDentsSelectionnees', dents);
                        } catch (e) {
                            console.warn('Odontogram: composant Livewire introuvable, clic ignoré', e);
                        }
                        return;
                    }

                    const tooth = selected[0];
                    if (!tooth) return;
                    const fdiAdulte = Number(tooth.notations.fdi);
                    if (estModeLait && FDI_ABSENTS_EN_LAIT.includes(fdiAdulte)) {
                        return;
                    }
                    const numDent = estModeLait
                        ? String(fdiLaitDepuisAdulte(fdiAdulte))
                        : tooth.notations.fdi;
                    try {
                        // Livewire.find() lance une exception (et non un simple
                        // retour null) quand l'id ne correspond à aucun composant
                        // monté — cas normal juste après un remount du composant
                        // hôte (ex: après création d'une consultation).
                        window.Livewire.find(idCourant)?.call('selectionnerDent', numDent);
                    } catch (e) {
                        console.warn('Odontogram: composant Livewire introuvable, clic ignoré', e);
                    }
                }}
            />
        </div>
    );
}

const mountedRoots = new WeakMap();

function renderInto(root, wireId, mode, conditionsParDent, modeMultiSelection, dentsSelectionnees) {
    root.render(
        <OdontogramReactif
            wireId={wireId}
            mode={mode}
            conditionsParDent={conditionsParDent}
            modeMultiSelection={modeMultiSelection}
            dentsSelectionnees={dentsSelectionnees}
        />
    );
}

function lireEtatDepuisDom(el) {
    return {
        mode: el.dataset.dentitionMode || 'adulte',
        conditions: JSON.parse(el.dataset.conditions || '{}'),
        modeMultiSelection: el.dataset.modeMultiSelection === 'true',
        dentsSelectionnees: JSON.parse(el.dataset.dentsSelectionnees || '[]'),
    };
}

function mountAll() {
    document.querySelectorAll('[data-odontogram-root]').forEach((el) => {
        if (mountedRoots.has(el)) {
            return;
        }
        const wireId = el.dataset.wireId;
        const etat = lireEtatDepuisDom(el);
        const root = createRoot(el);
        mountedRoots.set(el, { root, wireId });
        el.setAttribute('data-odontogram-mounted', 'true');
        renderInto(root, wireId, etat.mode, etat.conditions, etat.modeMultiSelection, etat.dentsSelectionnees);
    });
}

// Filet de sécurité : après chaque morph Livewire (ex: fermeture de la
// modale d'ajout d'acte), le <div wire:ignore> hébergeant l'odontogramme
// peut être repositionné dans l'arbre DOM par morphdom, ce qui peut faire
// perdre au navigateur le sous-arbre SVG interne de la librairie React
// sans que React n'en soit informé (donc sans redéclencher les effects qui
// réinjectent les labels FDI). On force un nouveau render dans ce cas.
function rerenderTousLesSchemas() {
    document.querySelectorAll('[data-odontogram-root]').forEach((el) => {
        const entry = mountedRoots.get(el);
        if (!entry) return;
        const etat = lireEtatDepuisDom(el);
        renderInto(entry.root, entry.wireId, etat.mode, etat.conditions, etat.modeMultiSelection, etat.dentsSelectionnees);
    });
}

document.addEventListener('livewire:init', () => {
    mountAll();

    Livewire.hook('morph.updated', () => {
        rerenderTousLesSchemas();
    });

    Livewire.on('conditions-updated', (event) => {
        const payload = Array.isArray(event) ? event[0] : event;
        document.querySelectorAll('[data-odontogram-root]').forEach((el) => {
            const entry = mountedRoots.get(el);
            if (!entry) return;
            const idCourant = resoudreWireIdCourant(el, entry.wireId);
            if (idCourant === payload.wireId) {
                const etat = lireEtatDepuisDom(el);
                renderInto(entry.root, entry.wireId, etat.mode, payload.conditions, etat.modeMultiSelection, etat.dentsSelectionnees);
            }
        });
    });

    Livewire.on('dentition-updated', (event) => {
        const payload = Array.isArray(event) ? event[0] : event;
        document.querySelectorAll('[data-odontogram-root]').forEach((el) => {
            const entry = mountedRoots.get(el);
            if (entry && entry.wireId === payload.wireId) {
                const etat = lireEtatDepuisDom(el);
                renderInto(entry.root, entry.wireId, payload.mode, etat.conditions, etat.modeMultiSelection, etat.dentsSelectionnees);
            }
        });
    });

    Livewire.on('selection-multiple-updated', (event) => {
        const payload = Array.isArray(event) ? event[0] : event;
        document.querySelectorAll('[data-odontogram-root]').forEach((el) => {
            const entry = mountedRoots.get(el);
            if (!entry) return;
            const idCourant = resoudreWireIdCourant(el, entry.wireId);
            if (idCourant === payload.wireId) {
                const etat = lireEtatDepuisDom(el);
                renderInto(entry.root, entry.wireId, etat.mode, etat.conditions, payload.modeMultiSelection, payload.dentsSelectionnees);
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', mountAll);

// Détecte l'apparition de conteneurs data-odontogram-root non encore montés,
// y compris quand Livewire réutilise un nœud existant par morph d'attributs
// (pas seulement un vrai ajout de nœud) — mountAll() est idempotente
// (mountedRoots.has(el)), donc l'appeler à chaque mutation pertinente est sûr.
let scanPlanifie = false;
function planifierScan() {
    if (scanPlanifie) return;
    scanPlanifie = true;
    queueMicrotask(() => {
        scanPlanifie = false;
        mountAll();
    });
}
const observer = new MutationObserver((mutations) => {
    const pertinent = mutations.some((m) => {
        if (m.type === 'childList') {
            return (
                Array.from(m.addedNodes).some(
                    (node) =>
                        node.nodeType === 1 &&
                        (node.matches?.('[data-odontogram-root]') ||
                            node.querySelector?.('[data-odontogram-root]'))
                ) || document.querySelector('[data-odontogram-root]:not([data-odontogram-mounted])')
            );
        }
        return false;
    });
    if (pertinent) {
        planifierScan();
    }
});
observer.observe(document.documentElement, { childList: true, subtree: true });
