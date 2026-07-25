<?php

namespace App\Http\Livewire;

use App\Models\Acte;
use App\Models\Assureur;
use App\Models\EvaluationDent;
use App\Models\Facture;
use App\Models\Medecin;
use App\Models\ObservationDent;
use App\Models\Patient;
use App\Models\PlanTraitementDentaire as PlanTraitementDentaireModel;
use App\Services\FacturationService;
use App\Traits\HasLazyLoadingPlaceholder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlanTraitementDentaire extends Component
{
    use HasLazyLoadingPlaceholder;

    public $patient;
    public $patientId;
    public $modeObservationsSeules = false;

    public $lignesPlan = [];
    public $dentSelectionnee = null;
    public $conditionsParDent = [];
    public $dentitionMode = 'adulte';

    public $showActeSelector = false;
    public $searchActe = '';
    public $filteredActes = [];
    public $selectedActeId = null;
    public $prixRef = null;

    public $modeMultiSelection = false;
    public $dentsSelectionnees = [];

    // Zone cliquée pour les actes HEMI_ARCADE / ARCADE / BOUCHE_ENTIERE :
    // 'hemi_sup_droite', 'hemi_sup_gauche', 'hemi_inf_droite',
    // 'hemi_inf_gauche', 'arcade_sup', 'arcade_inf', 'bouche_entiere'.
    public $zoneSelectionnee = null;

    // Dents FDI (adulte) par zone — quadrants 1-4, positions 1-8.
    const DENTS_PAR_ZONE = [
        'hemi_sup_droite' => ['11', '12', '13', '14', '15', '16', '17', '18'],
        'hemi_sup_gauche' => ['21', '22', '23', '24', '25', '26', '27', '28'],
        'hemi_inf_gauche' => ['31', '32', '33', '34', '35', '36', '37', '38'],
        'hemi_inf_droite' => ['41', '42', '43', '44', '45', '46', '47', '48'],
        'arcade_sup' => ['11', '12', '13', '14', '15', '16', '17', '18', '21', '22', '23', '24', '25', '26', '27', '28'],
        'arcade_inf' => ['31', '32', '33', '34', '35', '36', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48'],
        'bouche_entiere' => [
            '11', '12', '13', '14', '15', '16', '17', '18', '21', '22', '23', '24', '25', '26', '27', '28',
            '31', '32', '33', '34', '35', '36', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48',
        ],
    ];

    const LIBELLES_ZONE = [
        'hemi_sup_droite' => 'Hémi-arcade supérieure droite',
        'hemi_sup_gauche' => 'Hémi-arcade supérieure gauche',
        'hemi_inf_gauche' => 'Hémi-arcade inférieure gauche',
        'hemi_inf_droite' => 'Hémi-arcade inférieure droite',
        'arcade_sup' => 'Arcade supérieure',
        'arcade_inf' => 'Arcade inférieure',
        'bouche_entiere' => 'Toute la bouche',
    ];

    // Types d'acte pertinents pour chaque zone/mode d'ouverture du sélecteur.
    const TYPES_CIBLE_PAR_ZONE = [
        'hemi_sup_droite' => ['HEMI_ARCADE'],
        'hemi_sup_gauche' => ['HEMI_ARCADE'],
        'hemi_inf_gauche' => ['HEMI_ARCADE'],
        'hemi_inf_droite' => ['HEMI_ARCADE'],
        'arcade_sup' => ['ARCADE'],
        'arcade_inf' => ['ARCADE'],
        'bouche_entiere' => ['BOUCHE_ENTIERE', 'PATIENT'],
    ];

    public $ligneAFacturerId = null;
    public $facturesEnAttente = [];
    public $showFactureSelector = false;

    public $historiqueDent = [];
    public $nouvelleObservation = '';

    public $showEvaluationModal = false;
    public $evaluationEmail = '';
    public $evaluationDentine = '';
    public $evaluationPulpe = '';
    public $evaluationRacine = '';
    public $evaluationParodonte = '';

    const OPTIONS_EMAIL = [
        'sain' => 'Sain : Aspect lisse et translucide',
        'lesion_initiale' => "Lésion initiale : Tache blanche (white spot) ou brune sans cavité",
        'perte_substance' => 'Perte de substance : Ébréchure, usure, ou fêlure superficielle',
        'carie_amelaire' => "Carie amélaire : Cavitation limitée à l'émail uniquement",
    ];

    const OPTIONS_DENTINE = [
        'saine' => 'Saine : Non exposée',
        'exposee' => 'Exposée : Perte d\'émail (atrition, abrasion ou érosion)',
        'carie_dentinaire' => 'Carie dentinaire : Cavité profonde avec tissu ramolli (dentine cariée)',
        'dentine_tertiaire' => 'Dentine tertiaire : Réactionnelle/sclérotique (brune et dure)',
    ];

    const OPTIONS_PULPE = [
        'asymptomatique' => 'Asymptomatique : Test de vitalité positif normal',
        'hyperemie' => 'Hyperémie : Sensibilité transitoire au froid/chaud (réversible)',
        'pulpite_aigue' => 'Pulpite aiguë : Douleur violente, lancinante et spontanée (irréversible)',
        'necrose' => 'Nécrose : Mort de la pulpe, aucun ressenti aux tests thermiques',
    ];

    const OPTIONS_RACINE = [
        'saine' => 'Saine : Entièrement recouverts et protégés',
        'exposee' => 'Exposée : Récession de la gencive (dénudation radiculaire)',
        'carie_radiculaire' => 'Carie radiculaire : Cavitation au niveau du cément/collet',
        'fracture' => 'Fracture : Fêlure ou fracture radiculaire suspectée',
    ];

    const OPTIONS_PARODONTE = [
        'sain' => "Sain : Aucun signe d'inflammation périphérique",
        'desmodontite' => 'Desmodontite : Douleur aiguë à la pression verticale ou à la mastication',
        'abces' => "Abcès péri-apical : Infection localisée à l'extrémité de la racine (pus)",
    ];

    protected $rules = [
        'selectedActeId' => 'required|exists:actes,ID',
    ];

    public function mount($patient = null, $modeObservationsSeules = false)
    {
        $this->modeObservationsSeules = $modeObservationsSeules;

        if ($patient) {
            $this->patient = $patient;
            if (is_array($patient)) {
                $this->patientId = $patient['ID'] ?? $patient['id'] ?? null;
            } elseif (is_object($patient)) {
                $this->patientId = $patient->ID ?? $patient->id ?? null;
            }
        }

        $dateNaissance = null;
        if (is_array($patient)) {
            $dateNaissance = $patient['DtNaissance'] ?? null;
        } elseif (is_object($patient)) {
            $dateNaissance = $patient->DtNaissance ?? null;
        }

        if ($dateNaissance) {
            $age = Carbon::parse($dateNaissance)->age;
            $this->dentitionMode = $age < 13 ? 'lait' : 'adulte';
        }

        if ($this->patientId) {
            $this->loadPlan();
        }
    }

    public function basculerDentition()
    {
        $this->dentitionMode = $this->dentitionMode === 'adulte' ? 'lait' : 'adulte';
        $this->dispatch('dentition-updated', wireId: $this->getId(), mode: $this->dentitionMode);
    }

    const TYPES_ADULTE = [
        1 => 'Incisive centrale',
        2 => 'Incisive latérale',
        3 => 'Canine',
        4 => 'Première prémolaire',
        5 => 'Deuxième prémolaire',
        6 => 'Première molaire',
        7 => 'Deuxième molaire',
        8 => 'Troisième molaire (dent de sagesse)',
    ];

    const TYPES_LAIT = [
        1 => 'Incisive centrale de lait',
        2 => 'Incisive latérale de lait',
        3 => 'Canine de lait',
        4 => 'Première molaire de lait',
        5 => 'Deuxième molaire de lait',
    ];

    public function nomDent(?string $numDent): ?string
    {
        if (!$numDent || strlen($numDent) !== 2) {
            return null;
        }

        $quadrant = (int) $numDent[0];
        $position = (int) $numDent[1];

        return $quadrant >= 5
            ? (self::TYPES_LAIT[$position] ?? null)
            : (self::TYPES_ADULTE[$position] ?? null);
    }

    public function loadPlan()
    {
        $this->lignesPlan = PlanTraitementDentaireModel::forPatient($this->patientId)
            ->with(['acte', 'medecin'])
            ->orderBy('num_dent')
            ->orderBy('created_at')
            ->get();

        // Éclater les lignes groupées (num_dent = "11,12,13...") pour que
        // chaque dent du groupe soit colorée individuellement sur le schéma,
        // même si l'acte n'a qu'une seule ligne/facturation en base.
        $parDent = [];
        foreach ($this->lignesPlan as $ligne) {
            foreach (explode(',', $ligne->num_dent) as $numDent) {
                $parDent[$numDent][] = $ligne->statut;
            }
        }
        $this->conditionsParDent = collect($parDent)
            ->map(function ($statuts) {
                return in_array('planifie', $statuts) ? 'planifie'
                    : (in_array('en_cours', $statuts) ? 'en_cours' : 'termine');
            })
            ->toArray();

        // En mode observation seule (Dossier médical), le schéma reste neutre :
        // le statut du plan de traitement n'a pas sa place dans ce contexte.
        $this->dispatch(
            'conditions-updated',
            wireId: $this->getId(),
            conditions: $this->modeObservationsSeules ? [] : $this->conditionsParDent
        );

        if ($this->dentSelectionnee) {
            $this->loadHistoriqueDent();
        }
    }

    public function selectionnerDent(string $numDent)
    {
        if ($this->modeMultiSelection && !$this->modeObservationsSeules) {
            $this->toggleDentSelectionMultiple($numDent);
            return;
        }

        $this->dentSelectionnee = $numDent;
        $this->nouvelleObservation = '';
        $this->loadHistoriqueDent();

        if ($this->modeObservationsSeules) {
            $this->ouvrirEvaluation($numDent);
            return;
        }

        $this->zoneSelectionnee = null;
        $this->showActeSelector = true;
        $this->searchActe = '';
        $this->selectedActeId = null;
        $this->prixRef = null;
        $this->chargerActesDisponibles();
    }

    // Ouvre le sélecteur d'actes pour une zone prédéfinie (hémi-arcade,
    // arcade, toute la bouche), déclenché par les boutons de zone du
    // schéma plutôt que par le clic sur une dent précise.
    public function ouvrirActeSelectorZone(string $zone)
    {
        if (!isset(self::DENTS_PAR_ZONE[$zone])) {
            return;
        }

        $this->zoneSelectionnee = $zone;
        $this->dentSelectionnee = null;
        $this->dentsSelectionnees = self::DENTS_PAR_ZONE[$zone];
        $this->modeMultiSelection = false;
        $this->showActeSelector = true;
        $this->searchActe = '';
        $this->selectedActeId = null;
        $this->prixRef = null;
        $this->chargerActesDisponibles();
    }

    public function toggleModeMultiSelection()
    {
        $this->modeMultiSelection = !$this->modeMultiSelection;
        $this->dentsSelectionnees = [];
        $this->showActeSelector = false;
        $this->notifierSelectionMultiple();
    }

    public function toggleDentSelectionMultiple(string $numDent)
    {
        if (in_array($numDent, $this->dentsSelectionnees, true)) {
            $this->dentsSelectionnees = array_values(array_diff($this->dentsSelectionnees, [$numDent]));
        } else {
            $this->dentsSelectionnees[] = $numDent;
        }
        $this->notifierSelectionMultiple();
    }

    // Remplace intégralement la sélection par la liste de dents fournie par
    // le composant React (source de vérité en mode multi-sélection : la
    // librairie gère elle-même l'accumulation/retrait des dents cochées).
    public function definirDentsSelectionnees(array $dents)
    {
        $this->dentsSelectionnees = array_values(array_unique($dents));
        $this->notifierSelectionMultiple();
    }

    public function selectionnerToutesLesDents()
    {
        $toutes = $this->dentitionMode === 'lait'
            ? ['51','52','53','54','55','61','62','63','64','65','71','72','73','74','75','81','82','83','84','85']
            : ['11','12','13','14','15','16','17','18','21','22','23','24','25','26','27','28',
               '31','32','33','34','35','36','37','38','41','42','43','44','45','46','47','48'];

        $this->dentsSelectionnees = $toutes;
        $this->notifierSelectionMultiple();
    }

    public function deselectionnerToutesLesDents()
    {
        $this->dentsSelectionnees = [];
        $this->notifierSelectionMultiple();
    }

    private function notifierSelectionMultiple()
    {
        $this->dispatch(
            'selection-multiple-updated',
            wireId: $this->getId(),
            modeMultiSelection: $this->modeMultiSelection,
            dentsSelectionnees: $this->dentsSelectionnees
        );
    }

    public function ouvrirActeSelectorMultiple()
    {
        if (empty($this->dentsSelectionnees)) {
            return;
        }

        $this->zoneSelectionnee = null;
        $this->showActeSelector = true;
        $this->searchActe = '';
        $this->selectedActeId = null;
        $this->prixRef = null;
        $this->chargerActesDisponibles();
    }

    public function ouvrirEvaluation(string $numDent)
    {
        $this->dentSelectionnee = $numDent;
        $this->showEvaluationModal = true;

        $derniere = EvaluationDent::forPatientDent($this->patientId, $numDent)
            ->latest()
            ->first();

        $this->evaluationEmail = $derniere->etat_email ?? '';
        $this->evaluationDentine = $derniere->etat_dentine ?? '';
        $this->evaluationPulpe = $derniere->etat_pulpe ?? '';
        $this->evaluationRacine = $derniere->etat_racine ?? '';
        $this->evaluationParodonte = $derniere->etat_parodonte ?? '';
    }

    public function fermerEvaluation()
    {
        $this->showEvaluationModal = false;
    }

    public function sauvegarderEvaluation()
    {
        if (!$this->dentSelectionnee || !$this->patientId) {
            return;
        }

        $medecinId = Auth::user()->fkidmedecin ?: null;
        if ($medecinId && !Medecin::where('idMedecin', $medecinId)->exists()) {
            $medecinId = null;
        }

        EvaluationDent::create([
            'patient_id' => $this->patientId,
            'num_dent' => $this->dentSelectionnee,
            'etat_email' => $this->evaluationEmail ?: null,
            'etat_dentine' => $this->evaluationDentine ?: null,
            'etat_pulpe' => $this->evaluationPulpe ?: null,
            'etat_racine' => $this->evaluationRacine ?: null,
            'etat_parodonte' => $this->evaluationParodonte ?: null,
            'medecin_id' => $medecinId,
            'cabinet_id' => Auth::user()->fkidcabinet ?? null,
            'created_by' => Auth::id(),
        ]);

        $this->showEvaluationModal = false;
        $this->loadHistoriqueDent();
        session()->flash('message', 'Évaluation enregistrée.');
    }

    public function loadHistoriqueDent()
    {
        if (!$this->dentSelectionnee || !$this->patientId) {
            $this->historiqueDent = [];
            return;
        }

        $actes = PlanTraitementDentaireModel::forPatient($this->patientId)
            ->where(function ($q) {
                $q->where('num_dent', $this->dentSelectionnee)
                    ->orWhere('num_dent', 'like', $this->dentSelectionnee . ',%')
                    ->orWhere('num_dent', 'like', '%,' . $this->dentSelectionnee . ',%')
                    ->orWhere('num_dent', 'like', '%,' . $this->dentSelectionnee);
            })
            ->with('medecin')
            ->get()
            ->map(fn ($ligne) => [
                'type' => 'acte',
                'date' => $ligne->created_at,
                'libelle' => $ligne->acte_libelle,
                'statut' => $ligne->statut,
                'medecin' => $ligne->medecin->Nom ?? null,
            ]);

        $observations = ObservationDent::forPatientDent($this->patientId, $this->dentSelectionnee)
            ->with('medecin')
            ->get()
            ->map(fn ($obs) => [
                'type' => 'observation',
                'date' => $obs->created_at,
                'texte' => $obs->texte,
                'medecin' => $obs->medecin->Nom ?? null,
            ]);

        $evaluations = EvaluationDent::forPatientDent($this->patientId, $this->dentSelectionnee)
            ->with('medecin')
            ->get()
            ->map(function ($eval) {
                $resume = collect([
                    $eval->etat_email ? 'Émail : ' . (self::OPTIONS_EMAIL[$eval->etat_email] ?? $eval->etat_email) : null,
                    $eval->etat_dentine ? 'Dentine : ' . (self::OPTIONS_DENTINE[$eval->etat_dentine] ?? $eval->etat_dentine) : null,
                    $eval->etat_pulpe ? 'Pulpe : ' . (self::OPTIONS_PULPE[$eval->etat_pulpe] ?? $eval->etat_pulpe) : null,
                    $eval->etat_racine ? 'Racine : ' . (self::OPTIONS_RACINE[$eval->etat_racine] ?? $eval->etat_racine) : null,
                    $eval->etat_parodonte ? 'Parodonte : ' . (self::OPTIONS_PARODONTE[$eval->etat_parodonte] ?? $eval->etat_parodonte) : null,
                ])->filter()->values();

                return [
                    'type' => 'evaluation',
                    'date' => $eval->created_at,
                    'lignes' => $resume->toArray(),
                    'medecin' => $eval->medecin->Nom ?? null,
                ];
            });

        $this->historiqueDent = $actes->concat($observations)->concat($evaluations)
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    public function ajouterObservation()
    {
        $this->validate([
            'nouvelleObservation' => 'required|string|max:2000',
        ], [
            'nouvelleObservation.required' => 'Veuillez saisir une observation.',
        ]);

        if (!$this->dentSelectionnee || !$this->patientId) {
            return;
        }

        $medecinId = Auth::user()->fkidmedecin ?: null;
        if ($medecinId && !Medecin::where('idMedecin', $medecinId)->exists()) {
            $medecinId = null;
        }

        ObservationDent::create([
            'patient_id' => $this->patientId,
            'num_dent' => $this->dentSelectionnee,
            'texte' => $this->nouvelleObservation,
            'medecin_id' => $medecinId,
            'cabinet_id' => Auth::user()->fkidcabinet ?? null,
            'created_by' => Auth::id(),
        ]);

        $this->nouvelleObservation = '';
        $this->loadHistoriqueDent();
    }

    public function fermerActeSelector()
    {
        $this->showActeSelector = false;
        $this->selectedActeId = null;
        $this->searchActe = '';
        $this->filteredActes = [];
        if ($this->zoneSelectionnee) {
            $this->zoneSelectionnee = null;
            $this->dentsSelectionnees = [];
        }
    }

    public function updatedSearchActe($value)
    {
        if (!$this->selectedActeId) {
            $this->chargerActesDisponibles($value);
        }
    }

    // Charge la liste des actes disponibles, filtrée par $recherche si
    // fourni — appelée à l'ouverture de la modale (liste complète) et à
    // chaque frappe dans la barre de recherche (liste filtrée). Le filtre
    // par type_cible dépend du contexte d'ouverture : dent unique, zone
    // prédéfinie (hémi-arcade/arcade/bouche), ou sélection multiple libre.
    private function chargerActesDisponibles(string $recherche = '')
    {
        $typesCible = $this->typesCiblePertinents();

        $this->filteredActes = Acte::where('Acte', 'like', '%' . $recherche . '%')
            ->where('Masquer', 0)
            ->whereIn('type_cible', $typesCible)
            ->orderBy('nordre')
            ->limit(50)
            ->get();
    }

    // Détermine quels type_cible d'actes sont pertinents pour le contexte
    // d'ouverture actuel du sélecteur.
    private function typesCiblePertinents(): array
    {
        if ($this->zoneSelectionnee) {
            return self::TYPES_CIBLE_PAR_ZONE[$this->zoneSelectionnee] ?? [];
        }

        if ($this->modeMultiSelection && count($this->dentsSelectionnees) > 1) {
            return ['MULTI_DENTS', 'DENT'];
        }

        return ['DENT'];
    }

    public function selectActe($id)
    {
        $acte = Acte::find($id);
        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixRef = $acte->PrixRef;
        }
    }

    // Retourne, parmi $dents, celles où ce même acte est déjà planifié ou
    // en cours (statut non terminé) — un acte terminé n'empêche pas de
    // refaire le même acte plus tard sur la même dent.
    private function dentsAvecActeDejaEnCours(?int $acteId, array $dents): array
    {
        if (!$acteId || !$this->patientId) {
            return [];
        }

        $lignes = PlanTraitementDentaireModel::forPatient($this->patientId)
            ->where('acte_id', $acteId)
            ->whereIn('statut', ['planifie', 'en_cours'])
            ->get(['num_dent']);

        $dentsOccupees = [];
        foreach ($lignes as $ligne) {
            foreach (explode(',', $ligne->num_dent) as $numDent) {
                $dentsOccupees[$numDent] = true;
            }
        }

        return array_values(array_filter($dents, fn ($d) => isset($dentsOccupees[$d])));
    }

    public function ajouterActeAuPlan()
    {
        $this->validate();

        $dents = ($this->modeMultiSelection || $this->zoneSelectionnee) && !empty($this->dentsSelectionnees)
            ? $this->dentsSelectionnees
            : ($this->dentSelectionnee ? [$this->dentSelectionnee] : []);

        if (empty($dents) || !$this->patientId) {
            return;
        }

        $dentsEnDoublon = $this->dentsAvecActeDejaEnCours($this->selectedActeId, $dents);
        if (!empty($dentsEnDoublon)) {
            $acteNom = Acte::find($this->selectedActeId)->Acte ?? 'Cet acte';
            session()->flash(
                'error',
                $acteNom . ' est déjà planifié ou en cours sur la dent ' .
                    (count($dentsEnDoublon) > 1 ? 's ' : ' ') .
                    implode(', ', $dentsEnDoublon) . '.'
            );
            return;
        }

        $acte = Acte::find($this->selectedActeId);
        $medecinId = Auth::user()->fkidmedecin ?: null;
        if ($medecinId && !Medecin::where('idMedecin', $medecinId)->exists()) {
            $medecinId = null;
        }

        // Un acte forfaitaire (ex: détartrage) appliqué à plusieurs dents à la
        // fois ne doit être facturé qu'une seule fois : une seule ligne de
        // plan est créée, listant toutes les dents concernées, plutôt qu'une
        // ligne par dent au prix plein répété.
        PlanTraitementDentaireModel::create([
            'patient_id' => $this->patientId,
            'num_dent' => implode(',', $dents),
            'acte_id' => $this->selectedActeId,
            'acte_libelle' => $acte ? $acte->Acte : '',
            'medecin_id' => $medecinId,
            'statut' => 'planifie',
            'prix_ref' => $this->prixRef,
            'cabinet_id' => Auth::user()->fkidcabinet ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->fermerActeSelector();
        $this->dentsSelectionnees = [];
        $this->modeMultiSelection = false;
        $this->loadPlan();

        $message = count($dents) > 1
            ? 'Acte ajouté à ' . count($dents) . ' dents (forfait unique).'
            : 'Acte ajouté au plan de traitement.';
        session()->flash('message', $message);
    }

    public function demarrerLigne($id)
    {
        $ligne = PlanTraitementDentaireModel::find($id);
        if ($ligne && $ligne->statut === 'planifie') {
            $ligne->update([
                'statut' => 'en_cours',
                'updated_by' => Auth::id(),
            ]);
            $this->loadPlan();
        }
    }

    public function ouvrirFacturationLigne($id)
    {
        $this->ligneAFacturerId = $id;

        $this->facturesEnAttente = Facture::where('estfacturer', 0)
            ->where('IDPatient', $this->patientId)
            ->orderBy('DtFacture', 'desc')
            ->get();

        $this->showFactureSelector = true;
    }

    public function fermerFactureSelector()
    {
        $this->showFactureSelector = false;
        $this->ligneAFacturerId = null;
        $this->facturesEnAttente = [];
    }

    public function terminerEtFacturer($factureId)
    {
        $ligne = PlanTraitementDentaireModel::find($this->ligneAFacturerId);
        if (!$ligne || $ligne->statut === 'termine') {
            $this->fermerFactureSelector();
            return;
        }

        try {
            DB::beginTransaction();

            $detail = app(FacturationService::class)->ajouterActeAFacture(
                $factureId,
                $ligne->acte_id,
                $ligne->prix_ref ?? 0,
                1,
                $ligne->num_dent
            );

            $ligne->update([
                'statut' => 'termine',
                'facture_id' => $factureId,
                'detail_facture_id' => $detail->idDetfacture,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            $this->fermerFactureSelector();
            $this->loadPlan();
            session()->flash('message', 'Acte terminé et facturé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la facturation : ' . $e->getMessage());
        }
    }

    public function creerNouvelleFacturePourLigne()
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $medecinId = $user->fkidmedecin ?? null;

            if (!$medecinId) {
                throw new \Exception('Vous devez être associé à un médecin pour créer une facture.');
            }

            $factureData = Facture::generateUniqueFactureNumber($user->fkidcabinet);

            $fkidEtsAssurance = null;
            $istp = 0;
            $txpec = 0;
            $patient = Patient::find($this->patientId);
            if ($patient && $patient->Assureur) {
                $fkidEtsAssurance = $patient->Assureur;
                $istp = 1;
                $assureur = Assureur::find($patient->Assureur);
                $txpec = $assureur ? floatval($assureur->TauxdePEC) / 100 : 0;
            }

            $facture = Facture::create([
                'Nfacture' => $factureData['Nfacture'],
                'anneeFacture' => $factureData['anneeFacture'],
                'nordre' => $factureData['nordre'],
                'DtFacture' => Carbon::now(),
                'IDPatient' => $this->patientId,
                'ISTP' => $istp,
                'fkidEtsAssurance' => $fkidEtsAssurance,
                'TXPEC' => $txpec,
                'TotFacture' => 0,
                'TotalPEC' => 0,
                'TotalfactPatient' => 0,
                'FkidMedecinInitiateur' => $medecinId,
                'fkidCabinet' => $user->fkidcabinet,
                'user' => $user->NomComplet ?? $user->name,
                'TotReglPatient' => 0,
                'ReglementPEC' => 0,
                'PartLaboratoire' => 0,
                'MontantAffectation' => 0,
            ]);

            DB::commit();

            $this->terminerEtFacturer($facture->Idfacture);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la création de la facture : ' . $e->getMessage());
        }
    }

    public function supprimerLigne($id)
    {
        $ligne = PlanTraitementDentaireModel::find($id);
        if ($ligne && $ligne->isFacturable()) {
            $ligne->delete();
            $this->loadPlan();
            session()->flash('message', 'Ligne supprimée.');
        }
    }

    public function render()
    {
        return view('livewire.plan-traitement-dentaire');
    }
}
