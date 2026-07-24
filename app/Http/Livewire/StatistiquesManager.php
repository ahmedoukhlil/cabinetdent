<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use PDF;

class StatistiquesManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $medecin_id;
    public $date_debut;
    public $date_fin;

    public $canViewAll      = false; // finances.view
    public $canViewOwn      = false; // finances.own
    public $canPrintEtat    = false; // finances.view (impression état journalier)
    public $isOwnOnly       = false;

    public function mount()
    {
        $user = Auth::user();
        $this->canViewAll   = $user->hasPermission('finances.view');
        $this->canViewOwn   = $user->hasPermission('finances.own');
        $this->canPrintEtat = $user->hasPermission('finances.view');
        $this->isOwnOnly    = $this->canViewOwn && !$this->canViewAll;
    }

    public function resetFilters()
    {
        $this->reset(['medecin_id', 'date_debut', 'date_fin']);
        $this->resetPage();
    }

    // Méthodes pour réinitialiser la pagination quand les filtres changent
    public function updatedMedecinId()
    {
        $this->resetPage();
    }

    public function updatedDateDebut()
    {
        $this->resetPage();
    }

    public function updatedDateFin()
    {
        $this->resetPage();
    }

    public function exportPdf($par = 'medecin')
    {
        $operations = $this->getOperations();
        $medecins = Medecin::all();
        $periode = [$this->date_debut, $this->date_fin];
        $pdf = PDF::loadView('exports.statistiques', compact('operations', 'medecins', 'periode', 'par'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'statistiques.pdf');
    }

    private function baseQuery()
    {
        $user = Auth::user();
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet);

        if ($this->isOwnOnly) {
            $query->where('fkidmedecin', $user->fkidmedecin);
        } elseif ($this->medecin_id) {
            $query->where('fkidmedecin', $this->medecin_id);
        }

        if ($this->date_debut) {
            $query->whereDate('dateoper', '>=', $this->date_debut);
        }
        if ($this->date_fin) {
            $query->whereDate('dateoper', '<=', $this->date_fin);
        }
        return $query;
    }

    private function getOperations()
    {
        return $this->baseQuery()->orderBy('dateoper', 'desc')->orderBy('cle', 'desc')->paginate(10);
    }

    private function getAllOperationsForStats()
    {
        return $this->baseQuery()->get();
    }

    public function render()
    {
        $operations = $this->getOperations();
        $allOperations = $this->getAllOperationsForStats();
        $medecins = Medecin::all();

        // Calcul des totaux généraux sur toutes les opérations de la période
        // Si aucune période n'est sélectionnée, les totaux sont à zéro
        if (!$this->date_debut && !$this->date_fin) {
            $totalRecettes = 0;
            $totalDepenses = 0;
            $solde = 0;
            $totauxGenerauxParMoyenPaiement = [];
            $totauxParMedecin = [];
        } else {
            $totalRecettes = $allOperations->sum('entreEspece');
            $totalDepenses = $allOperations->sum('retraitEspece');
            $solde = $totalRecettes - $totalDepenses;

            // Totaux par mode de paiement sur toutes les opérations
            $typesPaiement = $allOperations->pluck('TypePAie')->filter()->unique()->values()->toArray();
            $totauxGenerauxParMoyenPaiement = collect($typesPaiement)->mapWithKeys(function($type) use ($allOperations) {
                $recettes = $allOperations->where('TypePAie', $type)->where('entreEspece', '>', 0)->sum('MontantOperation');
                $depenses = $allOperations->where('TypePAie', $type)->where('retraitEspece', '>', 0)->sum('MontantOperation');
                return [$type => [
                    'recettes' => $recettes,
                    'depenses' => $depenses,
                    'solde' => $recettes - $depenses
                ]];
            })->toArray();

            // Totaux par médecin sur toutes les opérations
            $medecinsMap = $medecins->keyBy('idMedecin');
            $totauxParMedecin = collect($medecinsMap)->mapWithKeys(function($medecin) use ($allOperations, $typesPaiement) {
                $medecinOperations = $allOperations->where('fkidmedecin', $medecin->idMedecin);
                $recettes = $medecinOperations->where('entreEspece', '>', 0)->sum('MontantOperation');
                $depenses = $medecinOperations->where('retraitEspece', '>', 0)->sum('MontantOperation');
                $modesPaiement = collect($typesPaiement)->mapWithKeys(function($type) use ($medecinOperations) {
                    $recettesType = $medecinOperations->where('TypePAie', $type)->where('entreEspece', '>', 0)->sum('MontantOperation');
                    $depensesType = $medecinOperations->where('TypePAie', $type)->where('retraitEspece', '>', 0)->sum('MontantOperation');
                    return [$type => [
                        'recettes' => $recettesType,
                        'depenses' => $depensesType,
                        'solde' => $recettesType - $depensesType
                    ]];
                })->filter(fn($totals) => $totals['recettes'] > 0 || $totals['depenses'] > 0)->toArray();
                return [$medecin->idMedecin => [
                    'nom' => $medecin->Nom,
                    'recettes' => $recettes,
                    'depenses' => $depenses,
                    'solde' => $recettes - $depenses,
                    'modes_paiement' => $modesPaiement
                ]];
            })->filter(fn($totals) => $totals['recettes'] > 0 || $totals['depenses'] > 0)->toArray();
        }

        return view('livewire.statistiques-manager', compact(
            'operations',
            'medecins',
            'totalRecettes',
            'totalDepenses',
            'solde',
            'totauxGenerauxParMoyenPaiement',
            'totauxParMedecin'
        ));
    }
} 