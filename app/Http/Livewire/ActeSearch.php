<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Acte;

class ActeSearch extends Component
{
    public $search = '';
    public $actes = [];
    public $selectedActeId = null;
    public $showDropdown = false;
    public $fkidassureur = null;

    protected $updatesQueryString = ['fkidassureur'];

    public function mount($fkidassureur = null)
    {
        $this->fkidassureur = $fkidassureur;
    }

    public function updatedSearch($value)
    {
        \Log::info('ActeSearch::updatedSearch', ['value' => $value, 'fkidassureur' => $this->fkidassureur]);
        $this->showDropdown = true;
        $query = Acte::where('Acte', 'like', '%' . $value . '%');
        if ($this->fkidassureur) {
            $query->where('fkidassureur', $this->fkidassureur);
        }
        $this->actes = $query
            ->select('ID', 'Acte', 'PrixRef')
            ->orderBy('Acte')
            ->limit(30)
            ->get()
            ->unique('Acte')
            ->values();
        \Log::info('Actes trouvés', ['count' => $this->actes->count()]);
    }

    public function selectActe($id)
    {
        \Log::info('ActeSearch::selectActe - Début', [
            'id' => $id,
            'type' => gettype($id),
            'raw_id' => $id
        ]);

        if (!$id) {
            \Log::info('ActeSearch::selectActe - ID null ou vide, sortie');
            return;
        }

        $id = is_string($id) ? (int)$id : $id;
        $this->selectedActeId = $id;
        $acte = Acte::find($id);
        \Log::info('ActeSearch::selectActe - Acte trouvé', [
            'acte' => $acte ? [
                'id' => $acte->ID,
                'nom' => $acte->Acte,
                'prix' => $acte->PrixRef
            ] : null
        ]);

        if ($acte) {
            $this->search = $acte->Acte;
            $this->showDropdown = false;
            $this->dispatch('acteSelected', $acte->ID, $acte->PrixRef);
            \Log::info('ActeSearch::selectActe - Événement émis', [
                'id' => $acte->ID,
                'prix' => $acte->PrixRef
            ]);
        }
    }

    public function render()
    {
        return view('livewire.acte-search');
    }
}
