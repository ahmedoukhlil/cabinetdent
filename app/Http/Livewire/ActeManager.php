<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Acte;
use App\Models\Assureur;

class ActeManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $search = '';
    public $selectedAssureur = '';
    public $perPage = 10;

    // Propriétés pour le formulaire
    public $acteId;
    public $acteNom;
    public $montant;
    public $assureurId;
    public $acteArab;

    // Modals
    public $showModal = false;
    public $showDeleteModal = false;
    public $acteToDelete;

    protected function rules()
    {
        return [
            'acteNom' => 'required|min:3',
            'montant' => 'required|numeric|min:0',
            'assureurId' => 'required|exists:assureurs,IDAssureur',
            'acteArab' => 'nullable',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedAssureur()
    {
        $this->resetPage();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        if ($id) {
            $acte = Acte::find($id);
            if ($acte) {
                $this->acteId = $acte->ID;
                $this->acteNom = $acte->Acte;
                $this->montant = $acte->PrixRef;
                $this->assureurId = $acte->fkidassureur;
                $this->acteArab = $acte->ActeArab;
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
        $maxNordre = Acte::max('nordre') ?? 0;
        $data = [
            'Acte' => $this->acteNom,
            'PrixRef' => $this->montant,
            'fkidassureur' => $this->assureurId,
            'ActeArab' => $this->acteArab,
        ];
        if ($this->acteId) {
            $acte = Acte::find($this->acteId);
            if ($acte) {
                $acte->update($data);
                session()->flash('message', 'Acte modifié avec succès.');
            }
        } else {
            $data['nordre'] = $maxNordre + 1;
            $data['user'] = auth()->user()->name ?? 'system';
            Acte::create($data);
            session()->flash('message', 'Acte créé avec succès.');
        }
        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->acteToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteActe()
    {
        $acte = Acte::find($this->acteToDelete);
        if ($acte) {
            $acte->delete();
            session()->flash('message', 'Acte supprimé avec succès.');
        }
        $this->showDeleteModal = false;
        $this->acteToDelete = null;
    }

    public function resetForm()
    {
        $this->acteId = null;
        $this->acteNom = '';
        $this->montant = '';
        $this->assureurId = '';
        $this->acteArab = '';
    }

    public function render()
    {
        $query = Acte::with('assureur')->orderBy('Acte');

        if ($this->search) {
            $query->where('Acte', 'like', '%' . $this->search . '%');
        }
        if ($this->selectedAssureur) {
            $query->where('fkidassureur', $this->selectedAssureur);
        }

        $actes = $query->paginate($this->perPage);
        $assureurs = Assureur::orderBy('LibAssurance')->get();

        return view('livewire.acte-manager', [
            'actes' => $actes,
            'assureurs' => $assureurs,
        ]);
    }
} 