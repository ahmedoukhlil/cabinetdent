<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DossierMedical;
use App\Models\ConsultationMedicale;
use App\Models\AnalysePatient;
use App\Models\Medecin;
use App\Models\Medicament;
use App\Models\Ordonnanceref;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DossierMedicalManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithFileUploads;
    public $patient;
    public $patientId;

    // Confirmation suppression
    public $showConfirmDeleteConsultation = false;
    public $confirmDeleteConsultationId   = null;
    public $showConfirmDeleteAnalyse      = false;
    public $confirmDeleteAnalyseId        = null;
    // Onglet actif : 'dossier' | 'nouvelle_consultation' | 'historique'
    public $onglet = 'dossier';

    // --- Dossier permanent ---
    public $dossierId = null;
    public $antecedents_personnels = '';
    public $antecedents_familiaux = '';
    public $antecedents_chirurgicaux = '';
    public $groupe_sanguin = '';
    public $allergies = '';
    public $maladies_chroniques = '';
    public $maladies_chroniques_selection = [];
    public $maladies_chroniques_autre = '';
    public $traitements_permanents = '';
    public $alertes_dentaires = '';
    public $alertes_dentaires_selection = [];
    public $alertes_dentaires_detail = '';

    const ALERTES_DENTAIRES = [
        'Anticoagulants / anti-agrégants',
        'Grossesse en cours',
        'Prothèse valvulaire',
        'Prothèse articulaire',
        'Allergie anesthésie locale',
        'Allergie latex',
        'Tabac',
        'Bruxisme',
    ];

    const MALADIES_FREQUENTES = [
        'HTA',
        'Diabète',
    ];
    public $notes_dossier = '';

    // --- Nouvelle consultation ---
    public $medecinId = '';
    public $date_consultation = '';
    public $motif = '';
    public $tension_arterielle = '';
    public $anesthesie = '';
    public $examen_clinique = '';
    public $diagnostic = '';
    public $conduite_a_tenir = '';
    public $notes_consultation = '';
    public $medicaments = [];
    public $examens = [];

    // --- Historique ---
    public $consultations = [];
    public $consultationOuverte = null;

    // --- Medecins ---
    public $medecins = [];

    // --- Recherche medicament ---
    public $searchMedicament = '';
    public $resultsMedicament = [];
    public $medicamentIndex = null;

    // --- Ordonnances ---
    public $ordonnances = [];
    public $ordonnancesSelectionnees = []; // IDs des ordonnances sélectionnées pour la consultation

    // --- Analyses / fichiers ---
    public $analyses = [];
    public $analysesFichiers = [];  // fichiers temporaires Livewire
    public $analyseLibelle = '';
    public $analyseType = 'biologie';
    public $analyseDate = '';
    public $analyseNotes = '';
    public $analyseConsultationId = null;
    public $analysePreviewId = null;  // ID de l'analyse en preview

    protected $listeners = [
        'patientSelected'  => 'updatePatient',
        'ordonnanceCreated' => 'loadOrdonnances',
    ];

    public function mount($patient = null)
    {
        if ($patient) {
            $this->patient   = $patient;
            $this->patientId = is_array($patient) ? ($patient['ID'] ?? null) : ($patient->ID ?? null);
        }
        $this->medecins = Medecin::where('fkidcabinet', Auth::user()->fkidcabinet)
            ->orderBy('Nom')->get()->toArray();
        $user = Auth::user();
        if ($user && !empty($user->fkidmedecin)) {
            $this->medecinId = $user->fkidmedecin;
        } elseif (!empty($this->medecins)) {
            $this->medecinId = $this->medecins[0]['idMedecin'];
        }
        $this->date_consultation = now()->format('Y-m-d\TH:i');
        if ($this->patientId) {
            // Seul l'onglet "Dossier permanent" est visible actuellement
            // (les autres onglets sont masqués) : ne charger que ces données
            // ici pour accélérer l'ouverture ; consultations/analyses/
            // ordonnances restent chargées à la demande par leurs propres
            // actions (ex: après sauvegarderConsultation).
            $this->loadDossier();
        }
    }

    public function loadDossier()
    {
        $d = DossierMedical::where('fkidPatient', $this->patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)->first();
        if ($d) {
            $this->dossierId               = $d->id;
            $this->antecedents_personnels   = $d->antecedents_personnels ?? '';
            $this->antecedents_familiaux    = $d->antecedents_familiaux ?? '';
            $this->antecedents_chirurgicaux = $d->antecedents_chirurgicaux ?? '';
            $this->groupe_sanguin           = $d->groupe_sanguin ?? '';
            $this->allergies                = $d->allergies ?? '';
            $this->maladies_chroniques      = $d->maladies_chroniques ?? '';
            $this->traitements_permanents   = $d->traitements_permanents ?? '';
            // Reconstituer la sélection depuis le texte sauvegardé
            $saved = array_map('trim', explode(',', $this->maladies_chroniques));
            $this->maladies_chroniques_selection = array_values(array_intersect($saved, self::MALADIES_FREQUENTES));
            $autres = array_diff($saved, self::MALADIES_FREQUENTES);
            $this->maladies_chroniques_autre = implode(', ', array_filter($autres));
            $this->notes_dossier            = $d->notes ?? '';

            $this->alertes_dentaires = $d->alertes_dentaires ?? '';
            $this->alertes_dentaires_selection = array_values(array_intersect(
                array_map('trim', explode(',', $this->alertes_dentaires)),
                self::ALERTES_DENTAIRES
            ));
            $this->alertes_dentaires_detail = $d->alertes_dentaires_detail ?? '';
        }
    }

    public function loadConsultations()
    {
        $this->consultations = ConsultationMedicale::where('fkidPatient', $this->patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->with('medecin')
            ->orderBy('date_consultation', 'desc')
            ->get()->toArray();
    }

    public function updatePatient($patient)
    {
        $this->patient   = $patient;
        $this->patientId = is_array($patient) ? ($patient['ID'] ?? null) : ($patient->ID ?? null);
        $this->loadDossier();
        $this->loadConsultations();
        $this->loadAnalyses();
        $this->loadOrdonnances();
    }

    public function loadOrdonnances()
    {
        if (!$this->patientId) return;

        $this->ordonnances = Ordonnanceref::where('fkidpatient', $this->patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->with(['ordonnances', 'prescripteur'])
            ->orderBy('dtPrescript', 'desc')
            ->get()
            ->toArray();
    }

    public function sauvegarderDossier()
    {
        // Construire maladies_chroniques depuis cases + autre
        $toutes = array_filter(array_merge(
            $this->maladies_chroniques_selection,
            array_map('trim', explode(',', $this->maladies_chroniques_autre))
        ));
        $this->maladies_chroniques = implode(', ', $toutes);
        $this->alertes_dentaires = implode(', ', array_filter($this->alertes_dentaires_selection));

        DossierMedical::updateOrCreate(
            ['fkidPatient' => $this->patientId, 'fkidCabinet' => Auth::user()->fkidcabinet],
            [
                'antecedents_personnels'    => $this->antecedents_personnels,
                'antecedents_familiaux'     => $this->antecedents_familiaux,
                'antecedents_chirurgicaux'  => $this->antecedents_chirurgicaux,
                'groupe_sanguin'            => $this->groupe_sanguin,
                'allergies'                 => $this->allergies,
                'maladies_chroniques'       => $this->maladies_chroniques,
                'traitements_permanents'    => $this->traitements_permanents,
                'alertes_dentaires'         => $this->alertes_dentaires,
                'alertes_dentaires_detail'  => $this->alertes_dentaires_detail,
                'notes'                     => $this->notes_dossier,
            ]
        );
        $this->loadDossier();
        session()->flash('success_dossier', 'Dossier medical mis a jour.');
    }

    public function ajouterMedicament()
    {
        $this->medicaments[] = ['nom' => '', 'posologie' => '', 'duree' => ''];
    }

    public function supprimerMedicament($index)
    {
        unset($this->medicaments[$index]);
        $this->medicaments = array_values($this->medicaments);
    }

    public function updatedSearchMedicament($value)
    {
        if (strlen(trim($value)) < 2) {
            $this->resultsMedicament = [];
            return;
        }
        $this->resultsMedicament = Medicament::where('Medicament', 'like', '%'.$value.'%')
            ->limit(8)->pluck('Medicament')->toArray();
    }

    public function selectMedicament($nom)
    {
        if ($this->medicamentIndex !== null && isset($this->medicaments[$this->medicamentIndex])) {
            $this->medicaments[$this->medicamentIndex]['nom'] = $nom;
        }
        $this->searchMedicament  = '';
        $this->resultsMedicament = [];
        $this->medicamentIndex   = null;
    }

    public function ajouterExamen()
    {
        $this->examens[] = ['type' => 'biologie', 'libelle' => ''];
    }

    public function supprimerExamen($index)
    {
        unset($this->examens[$index]);
        $this->examens = array_values($this->examens);
    }

    public function sauvegarderConsultation()
    {
        $this->validate(
            ['medecinId' => 'required', 'date_consultation' => 'required'],
            ['medecinId.required' => 'Veuillez selectionner un medecin.', 'date_consultation.required' => 'La date est obligatoire.']
        );
        try {
            ConsultationMedicale::create([
                'fkidPatient'           => $this->patientId,
                'fkidMedecin'           => $this->medecinId ?: null,
                'fkidCabinet'           => Auth::user()->fkidcabinet,
                'date_consultation'     => $this->date_consultation,
                'motif'                 => $this->motif,
                'tension_arterielle'    => $this->tension_arterielle,
                'anesthesie'            => $this->anesthesie,
                'examen_clinique'       => $this->examen_clinique,
                'diagnostic'            => $this->diagnostic,
                'conduite_a_tenir'      => $this->conduite_a_tenir,
                'medicaments_prescrits' => array_values(array_filter($this->medicaments, fn($m) => !empty($m['nom']))),
                'examens_demandes'      => array_values(array_filter($this->examens, fn($e) => !empty($e['libelle']))),
                'ordonnances_ids'       => array_values(array_map('intval', $this->ordonnancesSelectionnees)),
                'notes'                 => $this->notes_consultation,
            ]);
            $this->resetConsultationForm();
            $this->loadConsultations();
            $this->onglet = 'historique';
            session()->flash('success_consultation', 'Consultation enregistree.');
        } catch (\Exception $e) {
            Log::error('DossierMedical: sauvegarderConsultation', ['error' => $e->getMessage()]);
            session()->flash('error_consultation', 'Erreur : '.$e->getMessage());
        }
    }

    private function resetConsultationForm()
    {
        $this->motif = $this->tension_arterielle = $this->anesthesie = '';
        $this->examen_clinique = $this->diagnostic = $this->conduite_a_tenir = $this->notes_consultation = '';
        $this->medicaments = [];
        $this->examens = [];
        $this->ordonnancesSelectionnees = [];
        $this->date_consultation = now()->format('Y-m-d\TH:i');
    }

    public function confirmSupprimerConsultation($id)
    {
        $this->confirmDeleteConsultationId   = $id;
        $this->showConfirmDeleteConsultation = true;
    }

    public function supprimerConsultation($id = null)
    {
        $id = $id ?? $this->confirmDeleteConsultationId;
        $c = ConsultationMedicale::find($id);
        if ($c && $c->fkidCabinet == Auth::user()->fkidcabinet) {
            $c->delete();
            $this->loadConsultations();
        }
        $this->showConfirmDeleteConsultation = false;
        $this->confirmDeleteConsultationId   = null;
    }

    public function toggleConsultation($id)
    {
        $this->consultationOuverte = ($this->consultationOuverte === $id) ? null : $id;
    }

    // =========================================================================
    // Analyses / fichiers uploadés
    // =========================================================================

    public function loadAnalyses()
    {
        $this->analyses = AnalysePatient::where('fkidPatient', $this->patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->orderBy('date_analyse', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return array_merge($a->toArray(), [
                    'url'       => Storage::url($a->fichier_path),
                    'est_image' => str_starts_with($a->fichier_mime ?? '', 'image/'),
                    'est_pdf'   => $a->fichier_mime === 'application/pdf',
                ]);
            })->toArray();
    }

    public function uploadAnalyses()
    {
        $this->validate([
            'analysesFichiers'   => 'required|array|min:1',
            'analysesFichiers.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx',
            'analyseLibelle'     => 'required|string|max:255',
            'analyseDate'        => 'nullable|date',
        ], [
            'analysesFichiers.required'   => 'Sélectionnez au moins un fichier.',
            'analysesFichiers.*.max'      => 'Chaque fichier ne doit pas dépasser 10 Mo.',
            'analysesFichiers.*.mimes'    => 'Format accepté : JPG, PNG, PDF, DOC.',
            'analyseLibelle.required'     => 'Le libellé est obligatoire.',
        ]);

        try {
            foreach ($this->analysesFichiers as $fichier) {
                $path = $fichier->store(
                    'analyses/' . Auth::user()->fkidcabinet . '/' . $this->patientId,
                    'public'
                );
                AnalysePatient::create([
                    'fkidPatient'      => $this->patientId,
                    'fkidCabinet'      => Auth::user()->fkidcabinet,
                    'fkidConsultation' => $this->analyseConsultationId ?: null,
                    'libelle'          => $this->analyseLibelle,
                    'type'             => $this->analyseType,
                    'fichier_path'     => $path,
                    'fichier_nom'      => $fichier->getClientOriginalName(),
                    'fichier_mime'     => $fichier->getMimeType(),
                    'fichier_taille'   => $fichier->getSize(),
                    'date_analyse'     => $this->analyseDate ?: null,
                    'notes'            => $this->analyseNotes,
                ]);
            }

            $this->analysesFichiers = [];
            $this->analyseLibelle   = '';
            $this->analyseNotes     = '';
            $this->analyseDate      = '';
            $this->loadAnalyses();
            session()->flash('success_analyse', count($this->analysesFichiers) . ' fichier(s) uploadé(s).');
        } catch (\Exception $e) {
            Log::error('DossierMedical: uploadAnalyses', ['error' => $e->getMessage()]);
            session()->flash('error_analyse', 'Erreur upload : ' . $e->getMessage());
        }
    }

    public function confirmSupprimerAnalyse($id)
    {
        $this->confirmDeleteAnalyseId   = $id;
        $this->showConfirmDeleteAnalyse = true;
    }

    public function supprimerAnalyse($id = null)
    {
        $id = $id ?? $this->confirmDeleteAnalyseId;
        $a = AnalysePatient::find($id);
        if ($a && $a->fkidCabinet == Auth::user()->fkidcabinet) {
            Storage::disk('public')->delete($a->fichier_path);
            $a->delete();
            $this->loadAnalyses();
            if ($this->analysePreviewId === $id) {
                $this->analysePreviewId = null;
            }
        }
        $this->showConfirmDeleteAnalyse = false;
        $this->confirmDeleteAnalyseId   = null;
    }

    public function previewAnalyse($id)
    {
        $this->analysePreviewId = ($this->analysePreviewId === $id) ? null : $id;
    }

    public function render()
    {
        return view('livewire.dossier-medical-manager');
    }
}
