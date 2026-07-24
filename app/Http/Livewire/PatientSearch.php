<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class PatientSearch extends Component
{
    public $search = '';
    public $searchBy = 'telephone';
    public $patients = [];
    public $selectedPatient = null;
    public $isSearching = false;
    public $showResults = false;

    protected $listeners = [
        'clearPatient'       => 'clearPatient',
        'setPatientExterne'  => 'setPatientExterne',
    ];

    public function mount()
    {
    }

    public function updatedSearch()
    {
        if (strlen(trim($this->search)) >= 1) {
            $this->searchPatients();
        } else {
            $this->patients = [];
            $this->showResults = false;
        }
    }

    public function ouvrirCreationSiIntrouvable()
    {
        if ($this->showResults && count($this->patients) === 0 && strlen(trim($this->search)) >= 3) {
            $telephone = $this->searchBy === 'telephone' ? trim($this->search) : '';
            $this->dispatch('openNouveauPatientModal', ['telephone' => $telephone]);
            $this->search = '';
            $this->showResults = false;
        }
    }

    public function updatedSearchBy()
    {
        $this->search = '';
        $this->patients = [];
        $this->showResults = false;
    }

    public function searchPatients()
    {
        $this->isSearching = true;
        
        try {
            $user = Auth::user();
            
            if (!$user || !$user->fkidcabinet) {
                session()->flash('error', 'Utilisateur non authentifié ou sans cabinet.');
                return;
            }

            $query = Patient::query()
                ->where('fkidcabinet', $user->fkidcabinet)
                ->with(['assureur:IDAssureur,LibAssurance,TauxdePEC']);

            $search = trim($this->search);
            
            switch ($this->searchBy) {
                case 'telephone':
                    $query->where(function($q) use ($search) {
                        $q->where('Telephone1', 'like', '%' . $search . '%')
                          ->orWhere('Telephone2', 'like', '%' . $search . '%');
                    });
                    break;

                case 'nom':
                    $query->where(function($q) use ($search) {
                        $q->where('Nom', 'like', '%' . $search . '%')
                          ->orWhere('Prenom', 'like', '%' . $search . '%');
                    });
                    break;

                case 'identifiant':
                    $query->where('IdentifiantPatient', 'like', '%' . $search . '%');
                    break;

                default:
                    // Recherche générale sur tous les champs
                    $query->where(function($q) use ($search) {
                        $q->where('Nom', 'like', '%' . $search . '%')
                          ->orWhere('Prenom', 'like', '%' . $search . '%')
                          ->orWhere('IdentifiantPatient', 'like', '%' . $search . '%')
                          ->orWhere('Telephone1', 'like', '%' . $search . '%')
                          ->orWhere('Telephone2', 'like', '%' . $search . '%');
                    });
                    break;
            }

            $this->patients = $query->select([
                    'ID', 'Nom', 'Prenom', 'IdentifiantPatient',
                    'Telephone1', 'Telephone2', 'DtNaissance', 'Genre',
                    'Assureur'
                ])
                ->orderBy('Prenom')
                ->orderBy('Nom')
                ->limit(10)
                ->get();

            $this->showResults = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la recherche : ' . $e->getMessage());
        } finally {
            $this->isSearching = false;
        }
    }

    public function selectPatient($patientId)
    {
        try {
            $patient = Patient::find($patientId);

            if ($patient) {
                $assureur = $patient->assureur;

                $this->selectedPatient = [
                    'ID' => $patient->ID,
                    'NomPatient' => $patient->Nom,
                    'Prenom' => $patient->Prenom,
                    'IdentifiantPatient' => $patient->IdentifiantPatient,
                    'Telephone1' => $patient->Telephone1,
                    'Telephone2' => $patient->Telephone2,
                    'Adresse' => $patient->Adresse,
                    'Genre' => $patient->Genre,
                    'DtNaissance' => $patient->DtNaissance,
                    'Assureur' => $patient->Assureur,
                    'TauxPEC' => $assureur ? $assureur->TauxdePEC : 0,
                    'NomAssureur' => $assureur ? $assureur->LibAssurance : ''
                ];

                $this->search = '';
                $this->patients = [];
                $this->showResults = false;
                
                \Log::info('Patient sélectionné dans PatientSearch', $this->selectedPatient);
                $this->dispatch('patientSelected', $this->selectedPatient);
            } else {
                session()->flash('error', 'Patient non trouvé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la sélection du patient.');
        }
    }

    // Appelé depuis AccueilPatient quand un patient est sélectionné via la salle d'attente
    public function setPatientExterne($patientId)
    {
        $patient = Patient::find($patientId);
        if (!$patient) return;

        $assureur = $patient->assureur;

        $this->selectedPatient = [
            'ID'                 => $patient->ID,
            'NomPatient'         => $patient->NomContact ?? $patient->Nom,
            'Prenom'             => $patient->Prenom,
            'IdentifiantPatient' => $patient->IdentifiantPatient,
            'Telephone1'         => $patient->Telephone1,
            'Telephone2'         => $patient->Telephone2,
            'Adresse'            => $patient->Adresse,
            'Genre'              => $patient->Genre,
            'DtNaissance'        => $patient->DtNaissance,
            'Assureur'           => $patient->Assureur,
            'TauxPEC'            => $assureur ? $assureur->TauxdePEC : 0,
            'NomAssureur'        => $assureur ? $assureur->LibAssurance : '',
            'NomContact'         => $patient->NomContact,
        ];

        $this->search = '';
        $this->patients = [];
        $this->showResults = false;
    }

    public function clearPatient()
    {
        $this->search = '';
        $this->patients = [];
        $this->selectedPatient = null;
        $this->showResults = false;
        $this->dispatch('patientCleared');
        // Ne pas émettre patientSelected avec null, juste patientCleared
    }

    public function render()
    {
        return view('livewire.patient-search');
    }
} 