<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Dentspatient;
use Livewire\WithPagination;

class PatientManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    // Propriétés pour la recherche et le filtrage
    public $search = '';
    public $sortField = 'IdentifiantPatient';
    public $sortDirection = 'asc';
    public $showInactive = true; // Par défaut, afficher tous les patients
    public $showModal = false;
    public $isAssured = false;
    public $searchBy = 'all'; // all, name, nni, phone
    public $creationOnly = false;
    public $showDeleteConfirm = false;
    public $patientToDelete = null;
    public $patientToDeleteNom = '';

    // Propriétés pour le formulaire
    public $patientId;
    public $nom;
    public $prenom;
    public $nni;
    public $dateNaissance;
    public $genre;
    public $telephone1;
    public $telephone2;
    public $adresse;
    public $identifiantAssurance;
    public $assureur;
    public $matriculeFonct;
    public $nomContact;
    public $classerSous;
    public $isActive = true;
    public $identifiantPatient;

    // Propriétés pour l'historique des paiements
    public $showPaymentHistoryModal = false;
    public $selectedPatientId;
    public $paymentHistory = [];

    protected $listeners = [
        'openPatientCreateModal' => 'openModal',
    ];

    // Réinitialiser la page lors des changements de recherche ou de filtre
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedShowInactive()
    {
        $this->resetPage();
    }

    public function updatedSearchBy()
    {
        $this->resetPage();
    }

    public function rules()
    {
        return [
            'nom' => 'required|min:2|regex:/^[\p{L}\s-]+$/u',
            'nni' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if ($value === 'Non renseigné') {
                        return;
                    }
                    if ($value && !preg_match('/^[A-Za-z0-9\-_. ]+$/', $value)) {
                        $fail('Le NNI ne doit contenir que des lettres, chiffres, espaces ou - _ . ou être "Non renseigné".');
                    }
                }
            ],
            'dateNaissance' => 'nullable|date',
            'genre' => 'nullable|in:H,F',
            'telephone1' => 'required|min:8',
            'telephone2' => 'nullable|min:8|max:255',
            'adresse' => 'nullable',
            'identifiantAssurance' => 'nullable|min:1',
            'assureur' => 'nullable|integer',
            'matriculeFonct' => 'nullable|min:3',
            'nomContact' => 'nullable|min:3',
            'classerSous' => 'nullable|min:2',
            // 'identifiantPatient' => 'required|integer|unique:patients,IdentifiantPatient,' . ($this->patientId ?? 'NULL') . ',ID',
        ];
    }

    protected $messages = [
        'nom.required' => 'Le nom est requis',
        'nom.min' => 'Le nom doit contenir au moins 2 caractères',
        'nom.regex' => 'Le nom ne doit contenir que des lettres, espaces et tirets',
        'prenom.required' => 'Le prénom est requis',
        'prenom.min' => 'Le prénom doit contenir au moins 2 caractères',
        'prenom.regex' => 'Le prénom ne doit contenir que des lettres, espaces et tirets',
        'nni.regex' => 'Le NNI ne doit contenir que des lettres, chiffres, espaces ou - _ .',
        'dateNaissance.date' => 'La date de naissance n\'est pas valide',
        'dateNaissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui',
        'genre.in' => 'Le genre doit être H ou F',
        'telephone1.required' => 'Le numéro de téléphone est requis',
        'telephone1.min' => 'Le numéro de téléphone doit contenir au moins 8 caractères',
        'telephone2.min' => 'Le numéro de téléphone secondaire doit contenir au moins 8 caractères',
        'adresse.min' => 'L\'adresse doit contenir au moins 5 caractères',
        'identifiantAssurance.min' => 'L\'identifiant d\'assurance doit contenir au moins 3 caractères',
        'matriculeFonct.min' => 'Le matricule fonctionnaire doit contenir au moins 3 caractères',
        'nomContact.min' => 'Le nom du contact doit contenir au moins 3 caractères',
        'classerSous.min' => 'La classification doit contenir au moins 2 caractères'
    ];

    public function mount($creationOnly = false)
    {
        $this->creationOnly = $creationOnly;
        $this->resetForm();
        $this->showInactive = true; // Par défaut, afficher tous les patients
        
        // Si on est en mode création uniquement, ouvrir automatiquement le modal
        if ($this->creationOnly) {
            $this->openModal();
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        // Réinitialiser à la première page lors du changement de tri
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->patientId = null;
        $this->identifiantPatient = null;
        $this->nom = '';
        $this->prenom = '';
        $this->nni = '';
        $this->dateNaissance = '';
        $this->genre = '';
        $this->telephone1 = '';
        $this->telephone2 = '';
        $this->adresse = '';
        $this->identifiantAssurance = '';
        $this->assureur = '';
        $this->matriculeFonct = '';
        $this->nomContact = '';
        $this->classerSous = '';
        $this->isActive = true;
        $this->isAssured = false;
    }

    public function edit($id)
    {
        $patient = Patient::find($id);
        if ($patient) {
            $this->patientId = $patient->ID;
            $this->identifiantPatient = $patient->IdentifiantPatient;
            $this->nom = $patient->Nom;
            $this->prenom = $patient->Prenom;
            $this->nni = $patient->NNI;
            $this->dateNaissance = $patient->DtNaissance ? Carbon::parse($patient->DtNaissance)->format('Y-m-d') : '';
            $this->genre = $patient->Genre;
            $this->telephone1 = $patient->Telephone1;
            $this->telephone2 = $patient->Telephone2;
            $this->adresse = $patient->Adresse;
            $this->identifiantAssurance = $patient->IdentifiantAssurance;
            $this->assureur = $patient->Assureur;
            $this->matriculeFonct = $patient->MatriculeFonct;
            $this->nomContact = $patient->NomContact;
            $this->classerSous = $patient->ClasserSous;
            $this->isActive = !$patient->choix;
            $this->isAssured = !empty($patient->IdentifiantAssurance) || !empty($patient->Assureur);
        }
        $this->showModal = true;
    }

    public function openModal($data = [])
    {
        $this->resetForm();
        if (!$this->patientId) {
            $dernierIdentifiant = Patient::max('IdentifiantPatient') ?? 0;
            $this->identifiantPatient = $dernierIdentifiant + 1;
        }
        if (!empty($data['telephone'])) {
            $this->telephone1 = $data['telephone'];
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        
        // Si on est en mode création uniquement, fermer aussi le modal parent
        if ($this->creationOnly) {
            $this->dispatch('closeNouveauPatientModal');
        }
    }

    public function save()
    {
        $rules = $this->rules();
        $rules['identifiantPatient'] = 'required|integer|unique:patients,IdentifiantPatient,' . ($this->patientId ?? 'NULL') . ',ID';
        $this->validate($rules);

        try {
            $data = [
                'Nom' => $this->nom,
                'Prenom' => $this->nom,
                'NNI' => !empty($this->nni) ? $this->nni : null,
                'DtNaissance' => !empty($this->dateNaissance) ? $this->dateNaissance : null,
                'Genre' => !empty($this->genre) ? $this->genre : null,
                'Telephone1' => $this->telephone1,
                'Telephone2' => !empty($this->telephone2) ? $this->telephone2 : null,
                'Adresse' => !empty($this->adresse) ? $this->adresse : null,
                'IdentifiantAssurance' => $this->isAssured ? $this->identifiantAssurance : '',
                'Assureur' => $this->isAssured ? $this->assureur : 1,
                'MatriculeFonct' => $this->matriculeFonct,
                'NomContact' => $this->nom,
                'ClasserSous' => $this->classerSous,
                'choix' => !$this->isActive,
                'Dtajout' => now(),
                'fkidUser' => Auth::id(),
                'fkidcabinet' => Auth::user()->fkidcabinet,
                'IdentifiantPatient' => $this->identifiantPatient,
            ];

            if ($this->patientId) {
                $patient = Patient::find($this->patientId);
                if (!$patient) {
                    throw new \Exception('Patient non trouvé');
                }
                $patient->update($data);
                session()->flash('message', 'Patient mis à jour avec succès.');
            } else {
                $patient = Patient::create($data);
                if (!$patient) {
                    throw new \Exception('Erreur lors de la création du patient');
                }
                session()->flash('message', 'Patient créé avec succès.');
            }

            $this->resetForm();
            $this->closeModal();
            
            // Si on est en mode création uniquement, fermer aussi le modal parent après succès
            if ($this->creationOnly) {
                $this->dispatch('closeNouveauPatientModal');
                $this->dispatch('patientCreated', $patient->ID);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur création patient : ' . $e->getMessage());
            \Log::error('Stack trace : ' . $e->getTraceAsString());
            session()->flash('error', 'Erreur SQL : ' . $e->getMessage() . ' (code: ' . $e->getCode() . ')');
            // Ne pas fermer le modal ni reset le formulaire en cas d'erreur
        }
    }

    public function toggleStatus($id)
    {
        try {
            $patient = Patient::find($id);
            if ($patient) {
                $patient->choix = !$patient->choix;
                $patient->save();
                session()->flash('message', 'Statut du patient mis à jour avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la mise à jour du statut.');
        }
    }

    public function confirmDeletePatient($id)
    {
        $patient = Patient::find($id);
        if (!$patient) return;
        $this->patientToDelete = $id;
        $this->patientToDeleteNom = $patient->NomPatient ?? ($patient->Nom . ' ' . $patient->Prenom);
        $this->showDeleteConfirm = true;
    }

    public function deletePatient()
    {
        if (!$this->patientToDelete) return;

        try {
            DB::transaction(function () {
                $id = $this->patientToDelete;

                // Supprimer les détails de factures
                DB::table('detailfacturepatient')->whereIn('fkidfacture',
                    DB::table('facture')->where('IDPatient', $id)->pluck('Idfacture')
                )->delete();

                // Supprimer les factures
                DB::table('facture')->where('IDPatient', $id)->delete();

                // Supprimer les ordonnances et leurs détails
                $ordIds = DB::table('ordonnanceref')->where('fkidpatient', $id)->pluck('id');
                if ($ordIds->isNotEmpty()) {
                    DB::table('ordonnances')->whereIn('fkidrefOrd', $ordIds)->delete();
                }
                DB::table('ordonnanceref')->where('fkidpatient', $id)->delete();

                // Supprimer les rendez-vous
                DB::table('rendezvous')->where('fkidPatient', $id)->delete();

                // Supprimer les mouvements stock liés
                DB::table('mouvements_stock')->where('fkidPatient', $id)->delete();

                // Supprimer consultations et analyses
                DB::table('consultation_medicale')->where('fkidPatient', $id)->delete();
                DB::table('analyses_patient')->where('fkidPatient', $id)->delete();

                // Supprimer dossier médical
                DB::table('dossier_medical')->where('fkidPatient', $id)->delete();

                // Supprimer le patient
                Patient::destroy($id);
            });

            $this->showDeleteConfirm = false;
            $this->patientToDelete = null;
            $this->patientToDeleteNom = '';
            session()->flash('message', 'Patient et toutes ses données supprimés avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur suppression patient', ['error' => $e->getMessage()]);
            session()->flash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Patient::query()
            // Appliquer le filtre uniquement si l'utilisateur choisit de masquer les inactifs
            ->when(!$this->showInactive, function($query) {
                $query->where('choix', 0);
            })
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    switch($this->searchBy) {
                        case 'name':
                            $q->where('Nom', 'like', '%' . $this->search . '%')
                              ->orWhere('Prenom', 'like', '%' . $this->search . '%');
                            break;
                        case 'nni':
                            $q->where('NNI', 'like', '%' . $this->search . '%');
                            break;
                        case 'phone':
                            $q->where('Telephone1', 'like', '%' . $this->search . '%')
                              ->orWhere('Telephone2', 'like', '%' . $this->search . '%');
                            break;
                        default:
                            $q->where('Nom', 'like', '%' . $this->search . '%')
                              ->orWhere('Prenom', 'like', '%' . $this->search . '%')
                              ->orWhere('NNI', 'like', '%' . $this->search . '%')
                              ->orWhere('Telephone1', 'like', '%' . $this->search . '%')
                              ->orWhere('Telephone2', 'like', '%' . $this->search . '%');
                    }
                });
            })
            ->when($this->sortField === 'IdentifiantPatient', function($query) {
                // Tri numérique pour le N° Fiche
                $query->orderByRaw('CAST(IdentifiantPatient AS UNSIGNED) ' . $this->sortDirection);
            }, function($query) {
                // Tri normal pour les autres champs
                $query->orderBy($this->sortField, $this->sortDirection);
            });

        $patients = $query->with('assureur')->paginate(10);

        // Correction pagination : si la page courante est vide et > 1, on revient à la première page
        if ($patients->isEmpty() && $patients->currentPage() > 1) {
            $this->resetPage();
            $patients = $query->with('assureur')->paginate(10);
        }

        return view('livewire.patient-manager', [
            'patients' => $patients,
            'assureurs' => \App\Models\Assureur::orderBy('LibAssurance')->get()
        ]);
    }

    public function calculateAge($dateNaissance)
    {
        if (!$dateNaissance) return null;
        return Carbon::parse($dateNaissance)->age;
    }

    public function getDentalHistory($patientId)
    {
        return Dentspatient::where('fkidpatient', $patientId)
            ->orderBy('DtAjout', 'desc')
            ->get();
    }

    public function showPaymentHistory($patientId)
    {
        $this->selectedPatientId = $patientId;
        $this->paymentHistory = \App\Models\CaisseOperation::where('fkidTiers', $patientId)
            ->orderBy('dateoper', 'desc')
            ->get();
        $this->showPaymentHistoryModal = true;
    }

    public function closePaymentHistoryModal()
    {
        $this->showPaymentHistoryModal = false;
        $this->selectedPatientId = null;
        $this->paymentHistory = [];
    }
} 