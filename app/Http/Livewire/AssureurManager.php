<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Assureur;

class AssureurManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Propriétés pour le formulaire
    public $assureurId;
    public $libAssurance;
    public $tauxdePEC;

    // Modals
    public $showModal = false;
    public $showDeleteModal = false;
    public $assureurToDelete;

    protected $rules = [
        'libAssurance' => 'required|min:2',
        'tauxdePEC' => 'required|numeric|min:0|max:100',
    ];

    public function updatingSearch() { $this->resetPage(); }

    public function openModal($id = null)
    {
        $this->resetForm();
        if ($id) {
            $assureur = Assureur::where('IDAssureur', $id)->first();
            if ($assureur) {
                $this->assureurId = $assureur->IDAssureur;
                $this->libAssurance = $assureur->LibAssurance;
                $this->tauxdePEC = $assureur->TauxdePEC;
            }
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save()
    {
        $this->validate();
        $data = [
            'LibAssurance' => $this->libAssurance,
            'TauxdePEC' => $this->tauxdePEC,
        ];
        if ($this->assureurId) {
            $assureur = Assureur::where('IDAssureur', $this->assureurId)->first();
            if ($assureur) {
                $assureur->update($data);
                session()->flash('message', 'Assureur modifié avec succès.');
            }
        } else {
            Assureur::create($data);
            session()->flash('message', 'Assureur créé avec succès.');
        }
        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->assureurToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteAssureur()
    {
        $assureur = Assureur::where('IDAssureur', $this->assureurToDelete)->first();
        if ($assureur) {
            $assureur->delete();
            session()->flash('message', 'Assureur supprimé avec succès.');
        }
        $this->showDeleteModal = false;
        $this->assureurToDelete = null;
    }

    public function resetForm()
    {
        $this->assureurId = null;
        $this->libAssurance = '';
        $this->tauxdePEC = '';
    }

    public function render()
    {
        $query = Assureur::orderBy('LibAssurance');
        if ($this->search) {
            $query->where('LibAssurance', 'like', '%' . $this->search . '%');
        }
        $assureurs = $query->paginate($this->perPage);
        return view('livewire.assureur-manager', [
            'assureurs' => $assureurs,
        ]);
    }
} 