<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Patient;
use App\Models\Medecin;
use App\Models\Assureur;
use App\Models\RefTypePaiement;
use App\Models\Acte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\TypeUser;
use App\Models\User;

class AccueilPatient extends Component
{
    use WithPagination;

    // Constantes pour les clés de cache
    const CACHE_TTL = 3600; // 1 heure
    const CACHE_KEY_DOCTORS = 'active_doctors';
    const CACHE_KEY_ASSUREURS = 'assureurs';
    const CACHE_KEY_PAYMENT_TYPES = 'types_paiement';
    const CACHE_KEY_ACTES = 'active_actes';

    // États des modales
    public $showCreateModal = false;
    public $showCreateRdvModal = false;
    public $showPatientListModal = false;
    public $showGestionPatientsModal = false;
    public $showNouveauPatientModal = false;
    public $showActeModal = false;
    public $showAssureurModal = false;
    public $showCreatePatientModal = false;
    public $showPatientDetails = false;
    public $showPaymentHistory = false;
    public $showEditModal = false;
    public $showDeleteConfirmation = false;
    public $showCreateActeModal = false;
    public $showListeActesModal = false;
    public $showListeMedicamentsModal = false;
    public $showCaisseOperations = false;
    public $showDepenses = false;
    public $showUsersModal = false;
    public $showStatistiques = false;
    public $showMedecinsModal = false;
    public $showTypePaiementModal = false;
    public $showCabinetMenu = false;
    public $showPatientMenu = false;
    public $showOrdonnanceModal = false;
    public $showDashboardStock = false;
    public $showSalleAttenteModal = false;
    public $salleAttenteEnArrierePlan = false; // salle d'attente ouverte derrière un autre modal
    public $showSalleSoinsModal = false;
    public $showParametresCabinetModal = false;
    public $showActesPatientModal = false;

    // NOUVELLES PROPRIÉTÉS pour les sous-sections patient - Même logique que les sections principales
    public $showConsultation = false;
    public $showReglement = false;
    public $showRendezVous = false;
    public $showDossierMedical = false;
    
    // Onglet actif pour le modal RDV
    public $activeRdvTab = 'create';
    
    // Nombre de RDV à rappeler
    public $rdvRemindersCount = 0;

    // États des actions et données
    public $action = null;
    public $isDocteurProprietaire = false;
    public $isDocteur = false;
    public $isSecretaire = false;
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $selectedPatient = null;
    public $editingPatient;
    public $patientToDelete;

    // Données mises en cache
    public $medecins;
    public $assureurs;
    public $typesPaiement;
    public $actes;

    protected $listeners = [
        'patientSelected' => 'setPatient',
        'patientCleared' => 'clearSelectedPatient',
        'patientCreated' => 'handlePatientCreated',
        'closeCreateModal' => 'closeCreateModal',
        'closeNouveauPatientModal' => 'closeNouveauPatientModal',
        'openNouveauPatientModal'  => 'openNouveauPatientModal',
        'assureurCreated' => 'handleAssureurCreated',
        'acteCreated' => 'handleActeCreated',
        'refreshData' => 'refreshCachedData',
        'openModal' => 'handleOpenModal',
        'refreshReminders' => 'calculateRdvRemindersCount',
        'ordonnanceCreated' => 'handleOrdonnanceCreated',
        'fermerOrdonnanceModal' => 'fermerOrdonnanceModal',
        'fermerConsultationModal' => 'fermerConsultationModal',
        'fermerReglementModal' => 'fermerReglementModal',
        'fermerRendezVousModal' => 'fermerRendezVousModal',
        'fermerDossierMedicalModal' => 'fermerDossierMedicalModal',
        'fermerCaisseOperationsModal' => 'fermerCaisseOperationsModal',
        'fermerDepensesModal' => 'fermerDepensesModal',
        'fermerStatistiquesModal' => 'fermerStatistiquesModal',
        'fermerDashboardStockModal' => 'fermerDashboardStockModal',
        'patientSelectedFromSalle' => 'ouvrirDepuisSalleAttente',
        'fermerSalleAttenteModal' => 'fermerSalleAttenteModal'
    ];

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this->initializeRoles();
        $this->loadCachedData();
        $this->preloadActes();
        $this->calculateRdvRemindersCount();
        
        // Initialisation explicite des propriétés de menu
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;

        if (request()->has('patient_id')) {
            $patient = Patient::find(request()->patient_id);
            if ($patient) {
                $this->setPatient($patient);
            }
        }
    }

    private function initializeRoles()
    {
        $user = Auth::user();
        
        $this->isDocteurProprietaire = $user->isDocteurProprietaire();
        $this->isDocteur = $user->isDocteur();
        $this->isSecretaire = $user->isSecretaire();

        $this->canManageRdv = $this->isDocteurProprietaire || $this->isSecretaire || $this->isDocteur;
        $this->canViewAllRdv = $this->isDocteurProprietaire || $this->isSecretaire;
    }

    private function loadCachedData()
    {
        $this->medecins = Cache::remember(self::CACHE_KEY_DOCTORS, self::CACHE_TTL, function () {
            $query = User::select(['Iduser', 'NomComplet', 'fkidmedecin', 'fkidcabinet'])
                ->whereHas('typeuser', function ($query) {
                    $query->whereIn('Libelle', ['Docteur Propriétaire', 'Docteur']);
                })->where('ismasquer', 0);

            if ($this->isDocteur) {
                $query->where('Iduser', Auth::id());
            }

            return $query->get();
        });

        $this->assureurs = Cache::remember(self::CACHE_KEY_ASSUREURS, self::CACHE_TTL, function () {
            return Assureur::select(['IDAssureur', 'LibAssurance', 'TauxdePEC', 'SeuilDevis'])->get();
        });

        $this->typesPaiement = Cache::remember(self::CACHE_KEY_PAYMENT_TYPES, self::CACHE_TTL, function () {
            return RefTypePaiement::select(['idtypepaie', 'LibPaie'])->get();
        });
    }

    private function preloadActes()
    {
        return Cache::remember(self::CACHE_KEY_ACTES, self::CACHE_TTL, function () {
            return Acte::select(['ID', 'Acte', 'PrixRef', 'nordre', 'Masquer'])
                       ->where('Masquer', 0)
                       ->orderBy('nordre')
                       ->get();
        });
    }

    public function handleOpenModal($modalName)
    {
        switch ($modalName) {
            case 'liste-actes':
                $this->showListeActesModal = true;
                break;
            // Ajoutez d'autres cas si nécessaire
        }
    }

    public function ouvrirListeActesModal()
    {
        $this->showListeActesModal = true;
        $this->showCaisseOperations = false;
        $this->showStatistiques = false;
        $this->dispatch('listeActesModalOpened');
    }

    public function fermerListeActesModal()
    {
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
    }

    public function ouvrirListeMedicamentsModal()
    {
        $this->showListeMedicamentsModal = true;
        $this->showCaisseOperations = false;
        $this->showStatistiques = false;
        $this->dispatch('listeMedicamentsModalOpened');
    }

    public function fermerListeMedicamentsModal()
    {
        $this->showListeMedicamentsModal = false;
    }

    public function refreshCachedData()
    {
        Cache::forget(self::CACHE_KEY_DOCTORS);
        Cache::forget(self::CACHE_KEY_ASSUREURS);
        Cache::forget(self::CACHE_KEY_PAYMENT_TYPES);
        Cache::forget(self::CACHE_KEY_ACTES);
        $this->loadCachedData();
        $this->preloadActes();
    }

    public function resetState()
    {
        $this->reset([
            'showCreateModal',
            'showPatientListModal',
            'showGestionPatientsModal',
            'showNouveauPatientModal',
            'showActeModal',
            'showAssureurModal',
            'showCreatePatientModal',
            'showPatientDetails',
            'showPaymentHistory',
            'showEditModal',
            'showDeleteConfirmation',
            'showCreateActeModal',
            'showListeActesModal',
            'showListeMedicamentsModal',
            'showCaisseOperations',
            'showDepenses',
            'showPatientMenu',
            'showCabinetMenu',
            'action',
            'editingPatient',
            'patientToDelete'
        ]);
        
        // Réinitialisation manuelle des propriétés qui ne sont pas dans reset()
        $this->showStatistiques = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
    }

    public function closeAllSections()
    {
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
        $this->action = null;
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showDashboardStock = false;
        $this->showSalleAttenteModal = false;
        $this->showSalleSoinsModal = false;
        $this->showParametresCabinetModal = false;
        // Ne pas fermer showOrdonnanceModal ici, il sera géré par ouvrirOrdonnanceModal

        // NOUVELLES SOUS-SECTIONS PATIENT - Même logique que les sections principales
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showDossierMedical = false;
    }

    public function setAction($action)
    {
        if (!$this->selectedPatient && in_array($action, ['consultation', 'reglement', 'rendezvous', 'dossier'])) {
            return;
        }
        $this->action = $action;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showCabinetMenu = false;
        // Ne pas fermer le menu patient pour les actions du sous-menu
        // $this->showPatientMenu = false;
        $this->dispatch('actionChanged', $action);
    }

    // NOUVELLES MÉTHODES pour les sous-sections patient - Même logique que les sections principales
    public function showConsultation()
    {
        if (!$this->requirePermission('consultation.view')) return;
        if (!$this->selectedPatient) return;
        
        // Fermer les autres modals mais garder le menu patient ouvert
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showReglement = false;
        $this->showRendezVous = false;

        // Ouvrir le modal consultation
        $this->showConsultation = true;
        $this->showPatientMenu = true;
        
        // Forcer le re-render
        $this->dispatch('consultation-modal-opened');
    }

    public function fermerConsultationModal()
    {
        $this->showConsultation = false;
        $this->revenirSalleAttente();
    }

    public function showReglement()
    {
        if (!$this->requirePermission('facture.view|facture.view.own')) return;
        if (!$this->selectedPatient) return;
        
        // Fermer les autres modals mais garder le menu patient ouvert
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showRendezVous = false;
        
        // Ouvrir le modal reglement
        $this->showReglement = true;
        $this->showPatientMenu = true;
        
        // Forcer le re-render
        $this->dispatch('reglement-modal-opened');
    }

    public function fermerReglementModal()
    {
        $this->showReglement = false;
    }

    public function showRendezVous()
    {
        if (!$this->requirePermission('rendez-vous.view')) return;
        if (!$this->selectedPatient) return;
        
        // Fermer les autres modals mais garder le menu patient ouvert
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        
        // Ouvrir le modal rendez-vous
        $this->showRendezVous = true;
        $this->showPatientMenu = true;
        
        // Forcer le re-render
        $this->dispatch('rendez-vous-modal-opened');
    }

    public function fermerRendezVousModal()
    {
        $this->showRendezVous = false;
    }

    public function showDossierMedical()
    {
        if (!$this->requirePermission('dossier.view')) return;
        if (!$this->selectedPatient) return;

        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;

        $this->showDossierMedical = true;
        $this->showPatientMenu = true;

        $this->dispatch('dossier-medical-modal-opened');
    }

    public function fermerDossierMedicalModal()
    {
        $this->showDossierMedical = false;
        $this->revenirSalleAttente();
    }

    public function setPatient($patient)
    {
        // Toujours stocker un array : un objet Eloquent et un array se
        // sérialisent différemment côté Livewire (synthesizer distinct),
        // ce qui peut faire varier le wire:key des composants enfants
        // (ex: consultation-modal-{{ $patientId }}) et forcer un remount
        // intempestif qui casse leurs bindings JS déjà attachés.
        $this->selectedPatient = is_object($patient) ? $patient->toArray() : $patient;
        $this->action = null;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
        
        // NOUVELLES SOUS-SECTIONS PATIENT - Même logique que les sections principales
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        
        // Réinitialiser l'état sans affecter le modal de création de rendez-vous
        $this->resetState();
    }

    public function clearSelectedPatient()
    {
        $this->selectedPatient = null;
        $this->showPatientMenu = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showOrdonnanceModal = false;
    }

    // Gestionnaires d'événements
    public function handlePatientCreated($patientId)
    {
        $patient = Patient::find($patientId);
        if ($patient) {
            $this->setPatient($patient->toArray());
            $this->showCreatePatientModal = false;
            $this->showNouveauPatientModal = false;
            // Notifier PatientSearch pour afficher le patient sélectionné
            $this->dispatch('setPatientExterne', $patientId);
        }
    }

    public function handleAssureurCreated()
    {
        $this->refreshCachedData();
        $this->showAssureurModal = false;
    }

    public function handleActeCreated()
    {
        $this->refreshCachedData();
        $this->showActeModal = false;
    }

    // Méthodes de gestion des modales
    public function openGestionPatientsModal()
    {
        $this->showGestionPatientsModal = true;
    }

    public function closeGestionPatientsModal()
    {
        $this->showGestionPatientsModal = false;
    }

    public function openNouveauPatientModal($data = [])
    {
        $this->showNouveauPatientModal = true;
        // Émettre un événement Livewire pour ouvrir le modal de création dans PatientManager
        $this->dispatch('openPatientCreateModal', $data);
    }

    public function closeNouveauPatientModal()
    {
        $this->showNouveauPatientModal = false;
    }

    public function showCreateRdv()
    {
        $this->showCreateRdvModal = true;

        // Émettre un événement pour informer le composant CreateRendezVous du patient sélectionné
        if ($this->selectedPatient) {
            $this->dispatch('patientSelected', $this->selectedPatient);
        }
    }

    public function closeCreateRdvModal()
    {
        $this->showCreateRdvModal = false;
    }

    public function showCaisseOperations()
    {
        // Fermer les autres modals
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
        
        // Ouvrir le modal caisse operations
        $this->showCaisseOperations = true;
        
        // Forcer le re-render
        $this->dispatch('caisse-operations-modal-opened');
    }

    public function fermerCaisseOperationsModal()
    {
        $this->showCaisseOperations = false;
    }

    public function openDepenses()
    {
        if (!$this->requirePermission('depenses.view')) return;
        // Fermer les autres modals
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
        
        // Ouvrir le modal depenses
        $this->showDepenses = true;
        
        // Forcer le re-render
        $this->dispatch('depenses-modal-opened');
    }

    public function fermerDepensesModal()
    {
        $this->showDepenses = false;
    }

    public function ouvrirAssureurModal()
    {
        $this->showAssureurModal = true;
        $this->showStatistiques = false;
    }

    public function fermerAssureurModal()
    {
        $this->showAssureurModal = false;
    }

    public function showCreatePatient()
    {
        $this->showCreatePatientModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function openPatientListModal()
    {
        $this->showPatientListModal = true;
    }

    public function closePatientListModal()
    {
        $this->showPatientListModal = false;
    }

    public function ouvrirActeModal()
    {
        $this->showActeModal = true;
    }

    public function fermerActeModal()
    {
        $this->showActeModal = false;
    }

    public function openUsersModal()
    {
        if (!$this->requirePermission('user.view')) return;
        $this->showUsersModal = true;
        $this->showStatistiques = false;
    }

    public function closeUsersModal()
    {
        $this->showUsersModal = false;
    }

    public function showStatistiques()
    {
        if (!$this->requirePermission('statistiques.view')) return;
        // Fermer les autres modals
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showPatientMenu = false;

        // Ouvrir le modal statistiques
        $this->showStatistiques = true;
        
        // Forcer le re-render
        $this->dispatch('statistiques-modal-opened');
    }

    public function fermerStatistiquesModal()
    {
        $this->showStatistiques = false;
    }

    public function ouvrirMedecinsModal()
    {
        $this->showMedecinsModal = true;
    }

    public function fermerMedecinsModal()
    {
        $this->showMedecinsModal = false;
    }

    public function ouvrirTypePaiementModal()
    {
        $this->showTypePaiementModal = true;
    }

    public function fermerTypePaiementModal()
    {
        $this->showTypePaiementModal = false;
    }

    public function ouvrirDashboardStock()
    {
        // Fermer les autres modals
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;
        $this->showOrdonnanceModal = false;
        $this->showConsultation = false;
        $this->showReglement = false;
        $this->showRendezVous = false;
        $this->showPatientMenu = false;

        // Ouvrir le modal dashboard stock
        $this->showDashboardStock = true;
        
        // Forcer le re-render
        $this->dispatch('dashboard-stock-modal-opened');
    }

    public function fermerDashboardStockModal()
    {
        $this->showDashboardStock = false;
    }

    public function ouvrirSalleAttente()
    {
        if (!$this->requirePermission('salle-attente.view')) return;
        $this->closeAllSections();
        $this->showSalleAttenteModal = true;
    }

    public function fermerSalleAttenteModal()
    {
        $this->showSalleAttenteModal = false;
    }

    public function ouvrirSalleSoins()
    {
        if (!$this->requirePermission('salle-soins.view')) return;
        $this->closeAllSections();
        $this->showSalleSoinsModal = true;
    }

    public function fermerSalleSoinsModal()
    {
        $this->showSalleSoinsModal = false;
    }

    public function ouvrirParametresCabinet()
    {
        $this->closeAllSections();
        $this->showParametresCabinetModal = true;
    }

    public function fermerParametresCabinet()
    {
        $this->showParametresCabinetModal = false;
    }

    public function ouvrirActesPatientModal()
    {
        if (!$this->requirePermission('act.view')) return;
        if (!$this->selectedPatient) return;
        $this->closeAllSections();
        $this->showActesPatientModal = true;
        $this->showPatientMenu = true;
    }

    public function fermerActesPatientModal()
    {
        $this->showActesPatientModal = false;
        $this->revenirSalleAttente();
    }

    public function ouvrirDepuisSalleAttente($patientData)
    {
        // Sélectionner le patient depuis la salle d'attente
        $patient = \App\Models\Patient::find($patientData['ID'] ?? null);
        if (!$patient) return;

        $this->setPatient($patient);

        // Notifier PatientSearch pour afficher les infos du patient
        $this->dispatch('setPatientExterne', $patient->ID);

        // Ouvrir directement la bonne section (salle d'attente en arrière-plan)
        $action = $patientData['action'] ?? null;
        if ($action === 'dossier') {
            $this->salleAttenteEnArrierePlan = true;
            $this->showSalleAttenteModal = false;
            $this->dispatch('open-dossier-medical-modal');
        } elseif ($action === 'plan-traitement') {
            $this->salleAttenteEnArrierePlan = true;
            $this->showSalleAttenteModal = false;
            $this->dispatch('open-plan-traitement-modal');
        } elseif ($action === 'select') {
            $this->showPatientMenu = true;
        }
    }

    public function ouvrirOrdonnanceModal()
    {
        if (!$this->requirePermission('ordonnance.create')) return;
        \Log::info('ouvrirOrdonnanceModal appelé', [
            'selectedPatient' => $this->selectedPatient ? 'existe' : 'null',
            'showOrdonnanceModal_avant' => $this->showOrdonnanceModal
        ]);

        if (!$this->selectedPatient) {
            session()->flash('error', 'Veuillez sélectionner un patient.');
            \Log::warning('Aucun patient sélectionné');
            return;
        }

        // Fermer les autres modals mais garder le menu patient ouvert
        $this->showAssureurModal = false;
        $this->showListeActesModal = false;
        $this->showListeMedicamentsModal = false;
        $this->showUsersModal = false;
        $this->showMedecinsModal = false;
        $this->showTypePaiementModal = false;
        $this->showCreateRdvModal = false;
        $this->showCreatePatientModal = false;
        $this->showCaisseOperations = false;
        $this->showDepenses = false;
        $this->showStatistiques = false;

        // Ouvrir le modal ordonnance
        $this->showOrdonnanceModal = true;
        
        // Forcer le re-render
        $this->dispatch('ordonnance-modal-opened');
        
        \Log::info('showOrdonnanceModal mis à true', [
            'showOrdonnanceModal' => $this->showOrdonnanceModal
        ]);
    }

    public function fermerOrdonnanceModal()
    {
        $this->showOrdonnanceModal = false;
        $this->revenirSalleAttente();
    }

    private function revenirSalleAttente(): void
    {
        if ($this->salleAttenteEnArrierePlan) {
            $this->showSalleAttenteModal = true;
            $this->salleAttenteEnArrierePlan = false;
        }
    }

    public function handleOrdonnanceCreated($ordonnanceId)
    {
        session()->flash('message', 'Ordonnance créée avec succès.');
    }

    public function toggleCabinetMenu()
    {
        $this->showCabinetMenu = !$this->showCabinetMenu;
        if ($this->showCabinetMenu) {
            $this->closeAllSections();
            $this->showCabinetMenu = true;
            // Ajouter un délai pour l'animation
            $this->dispatch('menu-opened', menu: 'cabinet');
        } else {
            $this->dispatch('menu-closed', menu: 'cabinet');
        }
    }

    public function togglePatientMenu()
    {
        // Vérifier si un patient est sélectionné
        if (!$this->selectedPatient) {
            return;
        }
        
        $this->showPatientMenu = !$this->showPatientMenu;
        if ($this->showPatientMenu) {
            $this->closeAllSections();
            $this->showPatientMenu = true;
            // Ajouter un délai pour l'animation
            $this->dispatch('menu-opened', menu: 'patient');
        } else {
            $this->dispatch('menu-closed', menu: 'patient');
        }
    }

    public function calculateRdvRemindersCount()
    {
        try {
            // Utiliser le cache pour le compteur de rappels (5 minutes)
            $cacheKey = 'rdv_reminders_count_' . Auth::id() . '_' . now()->format('Y-m-d');
            
            $this->rdvRemindersCount = Cache::remember($cacheKey, 300, function () {
                $query = \App\Models\Rendezvou::select('IDRdv')
                    ->where('rdvConfirmer', '!=', 'Terminé')
                    ->where('rdvConfirmer', '!=', 'Annulé')
                    ->where('fkidcabinet', Auth::user()->fkidcabinet);

                // Si c'est un docteur simple, ne compter que ses rendez-vous
                if ($this->isDocteur && !$this->canViewAllRdv) {
                    $query->where('fkidMedecin', Auth::user()->fkidmedecin);
                }

                // Compter les RDV de demain par défaut
                $tomorrow = now()->addDay()->format('Y-m-d');
                return $query->whereDate('dtPrevuRDV', $tomorrow)->count();
            });
        } catch (\Exception $e) {
            $this->rdvRemindersCount = 0;
        }
    }

    public function getRdvARappelerCountProperty()
    {
        return \App\Models\Rendezvou::where('fkidcabinet', \Auth::user()->fkidcabinet)
            ->whereDate('dtPrevuRDV', now()->addDay()->toDateString())
            ->whereNotIn('rdvConfirmer', ['Terminé', 'Annulé'])
            ->count();
    }

    /**
     * Vérifie la permission et affiche un toast d'erreur si refusé.
     * Retourne true si l'accès est autorisé.
     */
    /**
     * Vérifie une ou plusieurs permissions (séparées par |, OU logique).
     * Retourne true si au moins une est accordée.
     */
    private function requirePermission(string $permission): bool
    {
        $perms = explode('|', $permission);
        foreach ($perms as $p) {
            if (Auth::user()->hasPermission(trim($p))) {
                return true;
            }
        }
        $this->dispatch('toast', ['message' => 'Accès refusé : permission insuffisante.', 'type' => 'error']);
        return false;
    }

    public function render()
    {
        return view('livewire.accueil-patient', [
            'rdvARappelerCount' => $this->rdvARappelerCount,
        ]);
    }
} 
