<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Medecin;
use App\Models\CaisseOperation;
use App\Models\Detailfacturepatient;
use App\Models\Facture;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Rendezvou;

class MedecinPaiementStats extends Component
{
    // Propriétés pour les finances
    public $medecin_id = '';
    public $periode = 'day';
    public $date_debut;
    public $date_fin;
    public $stats = [];
    public $medecinsList = [];
    public $totalRecettes = 0;
    public $totalDepenses = 0;
    public $recettesParJour = [];
    public $dateDebutFormatee;
    public $dateFinFormatee;
    public $showExpenses = false;
    public $isProprietaire = false;
    public $isMedecin = false;
    public $isSecretaire = false;
    public $canSelectMedecin = false;

    // Propriétés pour les rendez-vous
    public $rdv_medecin_id = ''; // Nouvelle variable pour les RDV
    public $rdv_date; // Nouvelle variable pour la date des RDV
    public $selectedRendezVous = [];
    public $selectAll = false;
    public $showPastRdv = true;
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $medecins = [];
    public $RendezVous = [];

    public function mount()
    {
        $user = Auth::user();
        $this->rdv_date = today()->toDateString();
        $this->initializePermissions();
        $this->initializeRendezVousPermissions();
        $this->loadMedecins();
        $this->loadRendezVous();
        
        // Initialise les dates par défaut (aujourd'hui)
        $this->periode = 'day';
        $this->dateDebutFormatee = Carbon::now()->format('d/m/Y');
        $this->dateFinFormatee = Carbon::now()->format('d/m/Y');
        
        // Si c'est un médecin, on force son ID
        if ($this->isMedecin) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    protected function initializePermissions()
    {
        $user = Auth::user();
        
        // Définir les flags de rôle
        $this->isProprietaire = $user->IdClasseUser == 3;
        $this->isMedecin = $user->IdClasseUser == 2;
        $this->isSecretaire = $user->IdClasseUser == 1;
        
        // Définir les permissions
        $this->showExpenses = $this->isProprietaire;
        $this->canSelectMedecin = $this->isProprietaire;
    }

    protected function initializeRendezVousPermissions()
    {
        $user = Auth::user();
        $this->canManageRdv = $user->hasPermission('rendez-vous.create');
        $this->canViewAllRdv = $user->hasPermission('rendez-vous.view');
        
        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    protected function loadMedecins()
    {
        $user = Auth::user();
        if ($this->isProprietaire) {
            $this->medecinsList = Medecin::select('idMedecin', 'Nom')->get()->toArray();
        } elseif ($this->isMedecin) {
            $this->medecinsList = Medecin::where('idMedecin', $user->fkidmedecin)
                ->select('idMedecin', 'Nom')
                ->get()
                ->toArray();
        } else {
            $this->medecinsList = [];
        }
    }

    protected function loadRendezVous()
    {
        try {
            if (!Carbon::parse($this->rdv_date)->isValid()) {
                $this->RendezVous = collect([]);
                return;
            }

            $user = Auth::user();
            $query = Rendezvou::with(['medecin', 'patient'])
                ->whereDate('dtPrevuRDV', $this->rdv_date);

            if ($this->rdv_medecin_id) {
                $query->where('fkidMedecin', $this->rdv_medecin_id);
            } elseif ($user->isDocteur() && !$user->isDocteurProprietaire()) {
                $query->where('fkidMedecin', $user->fkidmedecin);
            }

            if (!$this->showPastRdv) {
                $query->where('dtPrevuRDV', '>=', today());
            }

            $this->RendezVous = $query->orderBy('HeureRdv')->get();
        } catch (\Exception $e) {
            $this->RendezVous = collect([]);
        }
    }

    public function updatedPeriode()
    {
        if ($this->periode !== 'custom') {
            $this->date_debut = null;
            $this->date_fin = null;
        }
        $this->refreshData();
    }

    public function updatedMedecinId()
    {
        $this->refreshData();
    }

    public function updatedDateDebut()
    {
        if ($this->date_debut) {
            $this->dateDebutFormatee = Carbon::parse($this->date_debut)->format('d/m/Y');
        }
        $this->refreshData();
    }

    public function updatedDateFin()
    {
        if ($this->date_fin) {
            $this->dateFinFormatee = Carbon::parse($this->date_fin)->format('d/m/Y');
        }
        $this->refreshData();
    }

    public function updatedRdvDate()
    {
        $this->loadRendezVous();
    }

    public function updatedRdvMedecinId()
    {
        $this->loadRendezVous();
    }

    public function updatedShowPastRdv()
    {
        $this->loadRendezVous();
    }

    public function rechercher()
    {
        // Définir la période
        $dates = $this->getPeriodeDates();
        $dateDebut = $dates['debut'];
        $dateFin = $dates['fin'];
        
        // Met à jour les dates formatées
        if ($this->periode !== 'all') {
            $this->dateDebutFormatee = $dateDebut->format('d/m/Y');
            $this->dateFinFormatee = $dateFin->format('d/m/Y');
        }
    
        // Récupération des statistiques par mode de paiement
        $this->getStatistiquesPaiement($dateDebut, $dateFin);
    
        // Calculer les recettes totales
        $this->calculerRecettes($dateDebut, $dateFin);
    
        // Calculer les dépenses totales
        $this->calculerDepenses($dateDebut, $dateFin);
    
        // Récupérer l'évolution des recettes par jour
        $this->getRecettesParJour($dateDebut, $dateFin);
        
        // Supprimez ces appels de méthodes
        // $this->calculerNombrePatients($dateDebut, $dateFin);
        // $this->calculerStatistiquesActes($dateDebut, $dateFin);
    }

    private function getPeriodeDates()
    {
        switch($this->periode) {
            case 'day':
                $dateDebut = Carbon::today();
                $dateFin = Carbon::today()->endOfDay();
                break;
            case 'week':
                $dateDebut = Carbon::now()->startOfWeek();
                $dateFin = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $dateDebut = Carbon::now()->startOfMonth();
                $dateFin = Carbon::now()->endOfMonth();
                break;
            case 'year':
                $dateDebut = Carbon::now()->startOfYear();
                $dateFin = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $dateDebut = $this->date_debut ? Carbon::parse($this->date_debut) : Carbon::now()->startOfMonth();
                $dateFin = $this->date_fin ? Carbon::parse($this->date_fin)->endOfDay() : Carbon::now()->endOfMonth();
                break;
            default:
                $dateDebut = Carbon::now()->startOfMonth();
                $dateFin = Carbon::now()->endOfMonth();
        }
        return ['debut' => $dateDebut, 'fin' => $dateFin];
    }

    private function getStatistiquesPaiement($dateDebut, $dateFin)
    {
        try {
            // Calcul des totaux globaux
            $query = CaisseOperation::selectRaw('
                SUM(entreEspece) as total_recettes_global,
                SUM(retraitEspece) as total_depenses_global
            ');

            // Appliquer le filtre de date uniquement si ce n'est pas "all"
            if ($this->periode !== 'all') {
                $query->whereBetween('dateoper', [$dateDebut, $dateFin]);
            }

            $totauxGlobaux = $query->first();
            $this->totalRecettes = $totauxGlobaux->total_recettes_global ?? 0;
            $this->totalDepenses = $totauxGlobaux->total_depenses_global ?? 0;

            // Calcul des totaux par médecin
            $query = CaisseOperation::selectRaw('
                medecin,
                SUM(entreEspece) as total_recettes,
                SUM(retraitEspece) as total_depenses,
                COUNT(cle) as nombre_operations
            ');

            // Appliquer le filtre de date uniquement si ce n'est pas "all"
            if ($this->periode !== 'all') {
                $query->whereBetween('dateoper', [$dateDebut, $dateFin]);
            }

            // Appliquer le filtre de médecin
            if ($this->isMedecin) {
                $query->where('fkidmedecin', Auth::user()->fkidmedecin);
            } elseif ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }

            $results = $query->groupBy('medecin')
                ->orderBy('total_recettes', 'desc')
                ->get();

            // Regrouper par médecin
            $this->stats = [];
            foreach ($results as $result) {
                $this->stats[$result->medecin] = [
                    'total_recettes' => $result->total_recettes,
                    'total_depenses' => $result->total_depenses,
                    'nombre_operations' => $result->nombre_operations,
                    'pourcentage' => $this->totalRecettes > 0 ? 
                        round(($result->total_recettes / $this->totalRecettes) * 100, 2) : 0
                ];
            }
        } catch (\Exception $e) {
            $this->stats = [];
            $this->totalRecettes = 0;
            $this->totalDepenses = 0;
        }
    }
    
    private function calculerRecettes($dateDebut, $dateFin)
    {
        try {
            $query = CaisseOperation::query();

            // Appliquer le filtre de date uniquement si ce n'est pas "all"
            if ($this->periode !== 'all') {
                $query->whereBetween('dateoper', [$dateDebut, $dateFin]);
            }

            // Appliquer le filtre de médecin
            if ($this->isMedecin) {
                $query->where('fkidmedecin', Auth::user()->fkidmedecin);
            } elseif ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }

            $this->totalRecettes = $query->sum('entreEspece') ?: 0;
        } catch (\Exception $e) {
            $this->totalRecettes = 0;
        }
    }
    
    private function calculerDepenses($dateDebut, $dateFin)
    {
        if (!$this->showExpenses) {
            $this->totalDepenses = 0;
            return;
        }
        
        try {
            $query = CaisseOperation::query();

            // Appliquer le filtre de date uniquement si ce n'est pas "all"
            if ($this->periode !== 'all') {
                $query->whereBetween('dateoper', [$dateDebut, $dateFin]);
            }

            // Appliquer le filtre de médecin
            if ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }

            $this->totalDepenses = $query->sum('retraitEspece') ?: 0;
        } catch (\Exception $e) {
            $this->totalDepenses = 0;
        }
    }

    
    private function getRecettesParJour($dateDebut, $dateFin)
    {
        try {
            $query = CaisseOperation::selectRaw('DATE(dateoper) as date, SUM(entreEspece) as montant');

            // Appliquer le filtre de date uniquement si ce n'est pas "all"
            if ($this->periode !== 'all') {
                $query->whereBetween('dateoper', [$dateDebut, $dateFin]);
            }

            // Appliquer le filtre de médecin
            if ($this->isMedecin) {
                $query->where('fkidmedecin', Auth::user()->fkidmedecin);
            } elseif ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }

            $this->recettesParJour = $query->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => $item->montant];
                })->toArray();
        } catch (\Exception $e) {
            $this->recettesParJour = [];
        }
    }

    public function render()
    {
        $user = Auth::user();
        $isSecretaire = $user->IdClasseUser == 1;
        
        // Si c'est une secrétaire, on retourne une vue vide
        if ($isSecretaire) {
            return view('livewire.medecin-paiement-stats', [
                'showExpenses' => false,
                'canSelectMedecin' => false,
                'isMedecin' => false,
                'isProprietaire' => false,
                'isSecretaire' => true
            ]);
        }

        return view('livewire.medecin-paiement-stats', [
            'showExpenses' => $user->IdClasseUser == 3, // Seul le propriétaire voit les dépenses
            'canSelectMedecin' => $user->IdClasseUser == 3, // Seul le propriétaire peut choisir le médecin
            'isMedecin' => $user->IdClasseUser == 2, // Pour afficher des messages spécifiques aux médecins
            'isProprietaire' => $user->IdClasseUser == 3, // Pour afficher des messages spécifiques au propriétaire
            'isSecretaire' => false
        ]);
    }

    public function annulerRendezVous($id)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission d\'annuler des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire() && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez annuler que vos propres rendez-vous.');
                return;
            }

            if ($rdv) {
                $rdv->update(['rdvConfirmer' => 'annulé']);
                session()->flash('message', 'Rendez-vous annulé.');
                $this->loadRendezVous();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'annulation du rendez-vous.');
        }
    }

    public function confirmerRendezVous($id)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission de confirmer des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire() && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez confirmer que vos propres rendez-vous.');
                return;
            }

            if ($rdv) {
                $rdv->update(['rdvConfirmer' => 'confirmé']);
                session()->flash('message', 'Rendez-vous confirmé.');
                $this->loadRendezVous();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la confirmation du rendez-vous.');
        }
    }

    public function annulerSelection()
    {
        try {
            $user = Auth::user();

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission d\'annuler des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
                $invalidRdv = Rendezvou::whereIn('IDRdv', $this->selectedRendezVous)
                    ->where('fkidMedecin', '!=', $user->fkidmedecin)
                    ->exists();

                if ($invalidRdv) {
                    session()->flash('error', 'Vous ne pouvez annuler que vos propres rendez-vous.');
                    return;
                }
            }

            Rendezvou::whereIn('IDRdv', $this->selectedRendezVous)
                ->update(['rdvConfirmer' => 'annulé']);
            $this->selectedRendezVous = [];
            $this->selectAll = false;
            session()->flash('message', 'Rendez-vous sélectionnés annulés.');
            $this->loadRendezVous();
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'annulation des rendez-vous.');
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRendezVous = $this->RendezVous->pluck('IDRdv')->toArray();
        } else {
            $this->selectedRendezVous = [];
        }
    }

    public function refreshData()
    {
        $dates = $this->getPeriodeDates();
        $this->getStatistiquesPaiement($dates['debut'], $dates['fin']);
        $this->calculerRecettes($dates['debut'], $dates['fin']);
        if ($this->showExpenses) {
            $this->calculerDepenses($dates['debut'], $dates['fin']);
        }
        $this->getRecettesParJour($dates['debut'], $dates['fin']);
    }
}