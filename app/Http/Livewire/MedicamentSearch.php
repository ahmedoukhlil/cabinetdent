<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Medicament;

class MedicamentSearch extends Component
{
    public $search = '';
    public $medicaments = [];
    public $selectedMedicamentId = null;
    public $showDropdown = false;
    public $fkidtype = null; // 1=Médicament, 2=Analyse, 3=Radio

    protected $updatesQueryString = ['fkidtype'];

    public function mount($fkidtype = null)
    {
        $this->fkidtype = $fkidtype;
    }

    public function updatedSearch($value)
    {
        \Log::info('MedicamentSearch::updatedSearch', ['value' => $value, 'fkidtype' => $this->fkidtype]);
        $this->showDropdown = true;
        $query = Medicament::where('LibelleMedic', 'like', '%' . $value . '%');
        if ($this->fkidtype) {
            $query->where('fkidtype', $this->fkidtype);
        }
        $this->medicaments = $query
            ->select('IDMedic', 'LibelleMedic', 'PrixRef', 'fkidtype')
            ->orderBy('LibelleMedic')
            ->limit(30)
            ->get()
            ->unique('LibelleMedic')
            ->values();
        \Log::info('Médicaments trouvés', ['count' => $this->medicaments->count()]);
    }

    public function selectMedicament($id)
    {
        \Log::info('MedicamentSearch::selectMedicament - Début', [
            'id' => $id,
            'type' => gettype($id),
            'raw_id' => $id
        ]);

        if (!$id) {
            \Log::info('MedicamentSearch::selectMedicament - ID null ou vide, sortie');
            return;
        }

        $id = is_string($id) ? (int)$id : $id;
        $this->selectedMedicamentId = $id;
        $medicament = Medicament::find($id);
        \Log::info('MedicamentSearch::selectMedicament - Médicament trouvé', [
            'medicament' => $medicament ? [
                'id' => $medicament->IDMedic,
                'nom' => $medicament->LibelleMedic,
                'prix' => $medicament->PrixRef,
                'fkidtype' => $medicament->fkidtype
            ] : null
        ]);

        if ($medicament) {
            $this->search = $medicament->LibelleMedic;
            $this->showDropdown = false;
            $this->dispatch('medicamentSelected', $medicament->IDMedic, $medicament->PrixRef ?? 0, $medicament->fkidtype);
            \Log::info('MedicamentSearch::selectMedicament - Événement émis', [
                'id' => $medicament->IDMedic,
                'prix' => $medicament->PrixRef,
                'fkidtype' => $medicament->fkidtype
            ]);
        }
    }

    public function render()
    {
        return view('livewire.medicament-search');
    }
}

