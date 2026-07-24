<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use App\Models\CaisseOperation;
use App\Models\Typetier;
use App\Models\RefTypePaiement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class DepensesManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    // Propriétés pour le formulaire
    public $dateDepense;
    public $montant;
    public $motif;
    public $fkIdTypeTiers;
    public $TypePAie = '';

    // Propriétés pour les filtres
    public $dateDebut;
    public $dateFin;
    public $typeTiersFilter;

    // Propriétés pour l'édition
    public $editingId;
    public $isEditing = false;

    protected $rules = [
        'dateDepense' => 'required|date',
        'montant' => 'required|numeric|min:0',
        'motif' => 'required|string|max:255',
        'fkIdTypeTiers' => 'required|numeric',
        'TypePAie' => 'required|string'
    ];

    public function mount()
    {
        $this->dateDepense = now()->format('Y-m-d');
        $this->dateDebut = now()->startOfMonth()->format('Y-m-d');
        $this->dateFin = now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $user = Auth::user();
        
        // Récupérer les types de tiers (exclure Patients, Assureurs et Autres Recettes car ce sont des recettes)
        $typesTiers = Typetier::where('Estvisible', 1)
            ->whereNotIn('LibelleTypeTiers', ['Patients', 'Assureurs', 'Autres Recettes'])
            ->get();
        
        // Récupérer les dépenses filtrées
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet)
            ->where('retraitEspece', '>', 0) // Seulement les dépenses
            ->where('entreEspece', 0); // Pas d'entrées d'espèces

        // Appliquer les filtres
        if ($this->dateDebut) {
            $query->whereDate('dateoper', '>=', $this->dateDebut);
        }
        if ($this->dateFin) {
            $query->whereDate('dateoper', '<=', $this->dateFin);
        }
        if ($this->typeTiersFilter) {
            $query->where('fkIdTypeTiers', $this->typeTiersFilter);
        }

        // Restriction par utilisateur
        if ($user->IdClasseUser == 1) { // Secrétaire
            $query->where('fkiduser', $user->Iduser);
        }

        $depenses = $query->orderBy('dateoper', 'desc')
            ->paginate(15);

        // Calculer les totaux
        $totalDepenses = $query->sum('retraitEspece');
        $totalDepensesParType = $query->get()
            ->groupBy('fkIdTypeTiers')
            ->map(function ($group) {
                return $group->sum('retraitEspece');
            });

        // Récupérer les types de paiement depuis la table ref_type_paiement
        $typesPaiement = RefTypePaiement::orderBy('LibPaie')->get();

        return view('livewire.depenses-manager', [
            'depenses' => $depenses,
            'typesTiers' => $typesTiers,
            'typesPaiement' => $typesPaiement,
            'totalDepenses' => $totalDepenses,
            'totalDepensesParType' => $totalDepensesParType
        ]);
    }

    public function save()
    {
        $user = Auth::user();
        if (($user->IdClasseUser ?? 0) != 3) {
            session()->flash('error', 'Seul le médecin propriétaire peut créer ou modifier une dépense.');
            return;
        }

        $this->validate();
        
        // Validation supplémentaire : s'assurer que le type de tiers n'est pas Patient ou Assureur
        $typeTiers = Typetier::find($this->fkIdTypeTiers);
        if ($typeTiers && in_array($typeTiers->LibelleTypeTiers, ['Patients', 'Assureurs'])) {
            session()->flash('error', 'Les patients et assureurs ne peuvent pas être utilisés pour les dépenses (ce sont des recettes).');
            return;
        }

        $data = [
            'dateoper' => $this->dateDepense,
            'MontantOperation' => $this->montant,
            'designation' => $this->motif,
            'fkidTiers' => 0, // Pas besoin d'ID tiers spécifique
            'entreEspece' => 0,
            'retraitEspece' => $this->montant,
            'pourPatFournisseur' => 0,
            'pourCabinet' => 0,
            'fkiduser' => $user->Iduser,
            'exercice' => Carbon::parse($this->dateDepense)->year,
            'fkIdTypeTiers' => $this->fkIdTypeTiers,
            'fkidfacturebord' => 0,
            'DtCr' => now(),
            'fkidcabinet' => $user->fkidcabinet,
            'TypePAie' => $this->TypePAie,
            'fkidmedecin' => $user->Iduser, // Utiliser l'ID de l'utilisateur connecté comme médecin
            'medecin' => $user->Nom ?? 'Aucun' // Utiliser le nom de l'utilisateur connecté
        ];

        if ($this->isEditing) {
            CaisseOperation::where('cle', $this->editingId)->update($data);
            session()->flash('message', 'Dépense modifiée avec succès.');
        } else {
            CaisseOperation::create($data);
            session()->flash('message', 'Dépense ajoutée avec succès.');
        }

        $this->resetForm();
        $this->resetPage();
    }



    public function edit($id)
    {
        $user = Auth::user();
        if (($user->IdClasseUser ?? 0) != 3) {
            session()->flash('error', 'Seul le médecin propriétaire peut modifier une dépense.');
            return;
        }

        $depense = CaisseOperation::find($id);
        if ($depense) {
            $this->editingId = $depense->cle;
            $this->dateDepense = Carbon::parse($depense->dateoper)->format('Y-m-d');
            $this->montant = $depense->retraitEspece;
            $this->motif = $depense->designation;
            $this->fkIdTypeTiers = $depense->fkIdTypeTiers;
            $this->TypePAie = $depense->TypePAie;
            $this->isEditing = true;
        }
    }

    public function delete($id)
    {
        $user = Auth::user();
        if (($user->IdClasseUser ?? 0) != 3) {
            session()->flash('error', 'Seul le médecin propriétaire peut supprimer une dépense.');
            return;
        }

        $depense = CaisseOperation::find($id);
        if ($depense) {
            $depense->delete();
            session()->flash('message', 'Dépense supprimée avec succès.');
        }
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'dateDepense', 'montant', 'motif',
            'fkIdTypeTiers', 'TypePAie', 'editingId', 'isEditing'
        ]);
        $this->dateDepense = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
        $this->reset([
            'dateDebut', 'dateFin', 'typeTiersFilter'
        ]);
        $this->dateDebut = now()->startOfMonth()->format('Y-m-d');
        $this->dateFin = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }
}
