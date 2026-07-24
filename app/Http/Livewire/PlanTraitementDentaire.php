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

        $this->conditionsParDent = collect($this->lignesPlan)
            ->groupBy('num_dent')
            ->map(function ($lignes) {
                return $lignes->contains('statut', 'planifie') ? 'planifie'
                    : ($lignes->contains('statut', 'en_cours') ? 'en_cours' : 'termine');
            })
            ->toArray();

        $this->dispatch('conditions-updated', wireId: $this->getId(), conditions: $this->conditionsParDent);

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

        $this->showActeSelector = true;
        $this->searchActe = '';
        $this->filteredActes = [];
        $this->selectedActeId = null;
        $this->prixRef = null;
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

        $this->showActeSelector = true;
        $this->searchActe = '';
        $this->filteredActes = [];
        $this->selectedActeId = null;
        $this->prixRef = null;
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

        $medecinId = Auth::user()->fkidmedecin ?? null;
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
            ->where('num_dent', $this->dentSelectionnee)
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

        $medecinId = Auth::user()->fkidmedecin ?? null;
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
    }

    public function updatedSearchActe($value)
    {
        if (!$this->selectedActeId) {
            $this->filteredActes = Acte::where('Acte', 'like', '%' . $value . '%')
                ->where('Masquer', 0)
                ->limit(30)
                ->get();
        }
    }

    public function selectActe($id)
    {
        $acte = Acte::find($id);
        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixRef = $acte->PrixRef;
        }
    }

    public function ajouterActeAuPlan()
    {
        $this->validate();

        $dents = $this->modeMultiSelection && !empty($this->dentsSelectionnees)
            ? $this->dentsSelectionnees
            : ($this->dentSelectionnee ? [$this->dentSelectionnee] : []);

        if (empty($dents) || !$this->patientId) {
            return;
        }

        $acte = Acte::find($this->selectedActeId);
        $medecinId = Auth::user()->fkidmedecin ?? null;
        if ($medecinId && !Medecin::where('idMedecin', $medecinId)->exists()) {
            $medecinId = null;
        }

        foreach ($dents as $numDent) {
            PlanTraitementDentaireModel::create([
                'patient_id' => $this->patientId,
                'num_dent' => $numDent,
                'acte_id' => $this->selectedActeId,
                'acte_libelle' => $acte ? $acte->Acte : '',
                'medecin_id' => $medecinId,
                'statut' => 'planifie',
                'prix_ref' => $this->prixRef,
                'cabinet_id' => Auth::user()->fkidcabinet ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $this->fermerActeSelector();
        $this->dentsSelectionnees = [];
        $this->modeMultiSelection = false;
        $this->loadPlan();

        $message = count($dents) > 1
            ? 'Acte ajouté à ' . count($dents) . ' dents.'
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
