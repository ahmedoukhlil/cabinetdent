<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Rendezvou;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Acte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateRendezVous extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    // Propriétés du formulaire
    public $patient_id = '';
    public $selectedPatient = null;
    public $medecin_id = '';
    public $date_rdv = '';
    public $heure_rdv;
    public $acte_prevu = 'Consultation';
    public $rdv_confirmer = 'En Attente';
    // Suppression de $patient pour éviter les problèmes de type avec Livewire

    // La recherche de patients est maintenant gérée par le composant PatientSearch
    public $medecins = [];
    public $actes = [];

    // Propriétés pour les permissions
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $isDocteurProprietaire = false;
    public $isSecretaire = false;
    public $isDocteur = false;
    
    // Propriété pour l'URL d'impression
    public $printUrl = '';
    public $showPrintButton = false;
    
    // Propriété pour empêcher la fermeture du modal
    public $keepModalOpen = true;

    // Propriétés pour la gestion groupée
    public $selectedRdvIds = [];
    public $selectAll = false;
    public $showBulkEditModal = false;
    public $bulkEditData = [
        'newDate' => '',
        'startTime' => '',
        'interval' => 15
    ];

    protected $rules = [
        'patient_id' => 'required',
        'medecin_id' => 'required',
        'date_rdv' => 'required|date|after_or_equal:today',
        'heure_rdv' => 'required',
        'acte_prevu' => 'required|min:3',
        'rdv_confirmer' => 'required'
    ];

    protected $messages = [
        'patient_id.required' => 'Le patient est requis',
        'medecin_id.required' => 'Le médecin est requis',
        'date_rdv.required' => 'La date est requise',
        'date_rdv.date' => 'La date n\'est pas valide',
        'heure_rdv.required' => 'L\'heure est requise',
        'acte_prevu.required' => 'L\'acte prévu est requis',
        'acte_prevu.min' => 'L\'acte prévu doit contenir au moins 3 caractères',
        'rdv_confirmer.required' => 'Le statut est requis'
    ];

    protected $listeners = [
        'patientSelected' => 'handlePatientSelected',
        'patientCleared' => 'handlePatientCleared',
    ];

    // Empêcher le rechargement automatique du composant
    protected $queryString = [];

    public function mount($patient = null)
    {
        // Charger seulement les médecins nécessaires
        $this->medecins = Medecin::select('idMedecin', 'Nom')
            ->where('Masquer', 0)
            ->orderBy('Nom')
            ->get();
        // Charger seulement les actes nécessaires
        $this->actes = Acte::select('ID', 'Acte')
            ->where('Masquer', 0)
            ->orderBy('Acte')
            ->limit(20) // Limiter à 20 actes les plus utilisés
            ->get();
        $this->initializePermissions();
        
        // Gérer le patient passé en paramètre
        \Log::info('CreateRendezVous mount: patient reçu', ['patient' => $patient]);
        
        if ($patient) {
            if (is_array($patient)) {
                $this->patient_id = $patient['ID'];
                $this->selectedPatient = $patient;
            } else {
                $this->patient_id = $patient->ID;
                $this->selectedPatient = $patient->toArray();
            }
            
            \Log::info('CreateRendezVous mount: patient traité', [
                'patient_id' => $this->patient_id,
                'selectedPatient' => $this->selectedPatient
            ]);
            
            // Sauvegarder l'état dans la session
            session(['rdv_patient' => [
                'id' => $this->patient_id,
                'data' => $this->selectedPatient
            ]]);
        }
        
        // Initialiser la date par défaut à aujourd'hui
        $this->date_rdv = now()->format('Y-m-d');
        
        // Proposer automatiquement l'heure actuelle + 10 minutes
        $this->heure_rdv = now()->addMinutes(10)->format('H:i');
    }

    protected function initializePermissions()
    {
        $user = Auth::user();
        
        // Définir les rôles
        $this->isDocteurProprietaire = $user->isDocteurProprietaire();
        $this->isSecretaire = $user->isSecretaire();
        $this->isDocteur = $user->isDocteur() && !$user->isDocteurProprietaire();

        // Définir les permissions
        $this->canManageRdv = $this->isDocteurProprietaire || $this->isSecretaire || $this->isDocteur;
        $this->canViewAllRdv = $this->isDocteurProprietaire || $this->isSecretaire;
        
        // Si c'est un docteur simple, on force son ID
        if ($this->isDocteur) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    // Méthodes pour la gestion des cases à cocher
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Sélectionner tous les RDV de la page courante
            $this->selectedRdvIds = $this->getCurrentPageRdvIds();
        } else {
            // Désélectionner tous
            $this->selectedRdvIds = [];
        }
    }

    public function updatedSelectedRdvIds()
    {
        // Mettre à jour l'état de "sélectionner tout"
        $currentPageIds = $this->getCurrentPageRdvIds();
        $this->selectAll = !empty($currentPageIds) && 
                          count(array_intersect($this->selectedRdvIds, $currentPageIds)) === count($currentPageIds);
    }

    protected function getCurrentPageRdvIds()
    {
        $query = Rendezvou::select('IDRdv')
            ->orderBy('dtPrevuRDV', 'desc')
            ->orderBy('HeureRdv', 'desc');

        // Appliquer les mêmes filtres que dans render()
        if ($this->medecin_id) {
            $query->where('fkidMedecin', $this->medecin_id);
        }
        if ($this->date_rdv) {
            $query->whereDate('dtPrevuRDV', $this->date_rdv);
        }
        if ($this->isDocteur) {
            $query->where('fkidMedecin', Auth::user()->fkidmedecin);
        }

        return $query->pluck('IDRdv')->toArray();
    }

    // Méthodes pour le modal de modification groupée
    public function openBulkEditModal()
    {
        if (empty($this->selectedRdvIds)) {
            session()->flash('error', 'Veuillez sélectionner au moins un rendez-vous.');
            return;
        }

        // Initialiser les données par défaut
        $this->bulkEditData = [
            'newDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'interval' => 15
        ];

        $this->showBulkEditModal = true;
    }

    public function closeBulkEditModal()
    {
        $this->showBulkEditModal = false;
        $this->bulkEditData = [
            'newDate' => '',
            'startTime' => '',
            'interval' => 15
        ];
    }

    public function updateBulkRdv()
    {
        // Débogage
        \Log::info('updateBulkRdv appelée', [
            'selectedRdvIds' => $this->selectedRdvIds,
            'bulkEditData' => $this->bulkEditData
        ]);
        
        // Validation
        if (empty($this->selectedRdvIds)) {
            session()->flash('error', 'Aucun rendez-vous sélectionné.');
            return;
        }

        if (empty($this->bulkEditData['newDate']) || empty($this->bulkEditData['startTime'])) {
            session()->flash('error', 'Veuillez remplir tous les champs obligatoires.');
            return;
        }

        // Validation de la date
        if (Carbon::parse($this->bulkEditData['newDate'])->lt(Carbon::today())) {
            session()->flash('error', 'La date ne peut pas être antérieure à aujourd\'hui.');
            return;
        }

        try {
            // Utiliser une transaction pour garantir la cohérence
            \DB::beginTransaction();
            
            $currentTime = Carbon::parse($this->bulkEditData['newDate'] . ' ' . $this->bulkEditData['startTime']);
            $interval = (int)($this->bulkEditData['interval'] ?? 15);
            $updatedCount = 0;
            $orderNumber = 1; // Commencer à 1 pour les nouveaux RDV

            // Récupérer le dernier numéro d'ordre existant pour cette date et ce médecin
            $lastOrderNumber = Rendezvou::whereDate('dtPrevuRDV', $this->bulkEditData['newDate'])
                ->where('fkidcabinet', Auth::user()->fkidcabinet)
                ->max('OrdreRDV') ?? 0;

            foreach ($this->selectedRdvIds as $rdvId) {
                $rdv = Rendezvou::find($rdvId);
                
                if ($rdv && $rdv->fkidcabinet == Auth::user()->fkidcabinet) {
                    // Vérifier les permissions
                    if ($this->isDocteur && !$this->canViewAllRdv && $rdv->fkidMedecin != Auth::user()->fkidmedecin) {
                        continue; // Ignorer les RDV d'autres médecins
                    }

                    // Calculer le nouveau numéro d'ordre
                    $newOrderNumber = $lastOrderNumber + $orderNumber;

                    $rdv->update([
                        'dtPrevuRDV' => $currentTime->format('Y-m-d'),
                        'HeureRdv' => $currentTime->format('H:i:s'),
                        'rdvConfirmer' => 'En Attente', // Réinitialiser le statut
                        'OrdreRDV' => $newOrderNumber // Attribuer le nouveau numéro d'ordre
                    ]);

                    $updatedCount++;
                    $orderNumber++;
                    $currentTime->addMinutes($interval);
                }
            }

            \DB::commit();

            if ($updatedCount > 0) {
                session()->flash('message', "{$updatedCount} rendez-vous mis à jour avec succès.");
                
                // Vider la sélection
                $this->selectedRdvIds = [];
                $this->selectAll = false;
                
                // Fermer le modal
                $this->closeBulkEditModal();
                
                // Rafraîchir la page
                $this->resetPage();
            } else {
                session()->flash('error', 'Aucun rendez-vous n\'a pu être mis à jour.');
            }

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    public function createRendezVous()
    {
        $this->validate();

        // Vérification supplémentaire des permissions
        $user = Auth::user();
        if ($this->isDocteur && $this->medecin_id != $user->fkidmedecin) {
            session()->flash('error', 'Vous ne pouvez créer des rendez-vous que pour vous-même.');
            return;
        }

        // Vérification des conflits d'horaires
        if (Rendezvou::hasConflict($this->medecin_id, $this->date_rdv, $this->heure_rdv)) {
            session()->flash('error', 'Ce créneau horaire n\'est pas disponible. Le médecin a déjà un rendez-vous à cette heure.');
            return;
        }

        try {
            // Utiliser une transaction pour optimiser les performances
            \DB::beginTransaction();
            
            $rendezVous = Rendezvou::create([
                'fkidPatient' => $this->patient_id,
                'fkidMedecin' => $this->medecin_id,
                'dtPrevuRDV' => $this->date_rdv,
                'HeureRdv' => $this->heure_rdv,
                'ActePrevu' => $this->acte_prevu,
                'rdvConfirmer' => $this->rdv_confirmer,
                'DtAjRdv' => now(),
                'user' => Auth::id(),
                'fkidcabinet' => Auth::user()->fkidcabinet,
                'OrdreRDV' => Rendezvou::generateNextOrderNumber($this->date_rdv, $this->medecin_id)
            ]);

            \DB::commit();

            $this->reset(['patient_id', 'heure_rdv', 'acte_prevu']);
            $this->resetPage();
            session()->flash('message', 'Rendez-vous créé avec succès.');
            
            // Émettre un événement pour réinitialiser le composant PatientSearch
            $this->dispatch('clearPatient');

            // Notification salle d'attente
            $patientNom = Patient::find($this->patient_id)->NomContact ?? 'Patient';
            $this->dispatch('nouveau-rdv-notif',
                nom: $patientNom,
                heure: $this->heure_rdv ?? '',
                medecinId: $this->medecin_id,
            );
            $this->dispatch('rdvCree');

            // Retourner l'URL pour l'impression du reçu dans un nouvel onglet
            $this->printUrl = route('rendez-vous.print', ['id' => $rendezVous->IDRdv]);
            $this->showPrintButton = true;
            $this->dispatch('open-receipt', url: $this->printUrl);
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Erreur lors de la création du rendez-vous: ' . $e->getMessage());
        }
    }

    public function updatedDateRdv($value)
    {
        $this->resetPage();
        $this->selectedRdvIds = []; // Réinitialiser la sélection lors du changement de filtre
        
        // Proposer automatiquement l'heure actuelle + 10 minutes quand la date change
        if ($this->medecin_id) {
            $this->heure_rdv = now()->addMinutes(10)->format('H:i');
        }
    }

    public function updatedMedecinId($value)
    {
        $this->resetPage();
        $this->selectedRdvIds = []; // Réinitialiser la sélection lors du changement de filtre
        
        // Proposer automatiquement l'heure actuelle + 10 minutes quand le médecin change
        if ($value && $this->date_rdv) {
            $this->heure_rdv = now()->addMinutes(10)->format('H:i');
        }
    }

    // Suppression de la logique de recherche dupliquée - maintenant gérée par le composant PatientSearch

    public function printRendezVous()
    {
        if ($this->printUrl) {
            $this->dispatch('open-receipt', url: $this->printUrl);
        }
    }

    public function handlePatientSelected($patient)
    {
        \Log::info('CreateRendezVous: handlePatientSelected appelé', ['patient' => $patient]);
        
        if ($patient === null) {
            $this->patient_id = null;
            $this->selectedPatient = null;
        } else {
            // Le patient vient du composant PatientSearch sous forme d'array
            $this->patient_id = $patient['ID'];
            $this->selectedPatient = $patient;
        }
        
        // Sauvegarder l'état dans la session
        session(['rdv_patient' => [
            'id' => $this->patient_id,
            'data' => $this->selectedPatient
        ]]);
        
        \Log::info('CreateRendezVous: patient_id mis à jour', ['patient_id' => $this->patient_id]);
        
        // Forcer la mise à jour des propriétés
        $this->dispatch('refresh');
    }

    public function handlePatientCleared()
    {
        $this->patient_id = null;
        $this->selectedPatient = null;
        session()->forget('rdv_patient');
        $this->dispatch('clearPatient');
    }

    public function getTotalRdvJourProperty()
    {
        return \App\Models\Rendezvou::whereDate('dtPrevuRDV', now()->toDateString())->count();
    }

    // Méthode supprimée — elle entrait en conflit avec la propriété publique $selectedPatient

    public function changerStatutRendezVous($id, $nouveauStatut)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission de modifier des rendez-vous.');
                return;
            }

            // Si c'est un docteur simple, vérifier que le rendez-vous lui appartient
            if ($this->isDocteur && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez modifier que vos propres rendez-vous.');
                return;
            }

            $statutsValides = ['En Attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
            
            if (!in_array($nouveauStatut, $statutsValides)) {
                session()->flash('error', 'Statut invalide.');
                return;
            }

            // Utiliser la méthode centralisée du modèle
            $result = Rendezvou::updateStatusWithConflictManagement($id, $nouveauStatut);
            
            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la modification du statut: ' . $e->getMessage());
        }
    }

    public function hydrate()
    {
        // Restaurer l'état du patient depuis la session si nécessaire
        if (!$this->selectedPatient && session()->has('rdv_patient')) {
            $rdvPatient = session('rdv_patient');
            $this->patient_id = $rdvPatient['id'];
            $this->selectedPatient = $rdvPatient['data'];
        }
    }

    public function dehydrate()
    {
        // Sauvegarder l'état du patient dans la session
        if ($this->selectedPatient) {
            session(['rdv_patient' => [
                'id' => $this->patient_id,
                'data' => $this->selectedPatient
            ]]);
        }
    }

    public function render()
    {
        $query = Rendezvou::query()
            ->with(['patient:ID,Nom,Telephone1', 'medecin:idMedecin,Nom']) // Retirer Prenom
            ->orderBy('dtPrevuRDV', 'desc')
            ->orderBy('HeureRdv', 'desc');

        // Si un médecin est sélectionné, filtrer par ce médecin
        if ($this->medecin_id) {
            $query->where('fkidMedecin', $this->medecin_id);
        }

        // Si une date est sélectionnée, filtrer par cette date
        if ($this->date_rdv) {
            $query->whereDate('dtPrevuRDV', $this->date_rdv);
        }

        // Si c'est un docteur simple, ne montrer que ses rendez-vous
        if ($this->isDocteur) {
            $query->where('fkidMedecin', Auth::user()->fkidmedecin);
        }

        $rendezVous = $query->paginate(8); // Réduire à 8 éléments par page

        return view('livewire.create-rendez-vous', [
            'rendezVous' => $rendezVous,
            'totalRdvJour' => $this->totalRdvJour,
        ])->layout('layouts.app');
    }

    /**
     * Propose automatiquement le prochain créneau disponible
     */
    public function proposerProchainCreneau()
    {
        if (!$this->medecin_id || !$this->date_rdv) {
            session()->flash('error', 'Veuillez d\'abord sélectionner un médecin et une date.');
            return;
        }

        $prochainCreneau = Rendezvou::getProchainCreneauPropose($this->medecin_id, $this->date_rdv);
        
        if ($prochainCreneau) {
            $this->heure_rdv = $prochainCreneau;
            session()->flash('message', 'Créneau proposé : ' . $prochainCreneau . ' (dernier RDV + 10 min)');
        } else {
            session()->flash('error', 'Aucun créneau disponible pour cette date. Tous les créneaux sont occupés.');
        }
    }
}