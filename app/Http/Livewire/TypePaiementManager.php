<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RefTypePaiement;

class TypePaiementManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $type_id = null;
    public $LibPaie = '';
    public $editMode = false;
    public $showDeleteConfirm = false;
    public $typeToDeleteId = null;
    public $typeToDeleteNom = '';

    protected $rules = [
        'LibPaie' => 'required|string|max:255',
    ];

    public function updating($property)
    {
        if ($property === 'LibPaie') {
            $this->resetPage();
        }
    }

    public function resetForm()
    {
        $this->type_id = null;
        $this->LibPaie = '';
        $this->editMode = false;
    }

    public function saveTypePaiement()
    {
        $this->validate();
        if ($this->editMode && $this->type_id) {
            $type = RefTypePaiement::find($this->type_id);
            if ($type) {
                $type->update([
                    'LibPaie' => $this->LibPaie,
                ]);
            }
        } else {
            RefTypePaiement::create([
                'LibPaie' => $this->LibPaie,
            ]);
        }
        $this->resetForm();
        $this->resetPage();
    }

    public function editTypePaiement($id)
    {
        $type = RefTypePaiement::find($id);
        if ($type) {
            $this->type_id = $type->idtypepaie;
            $this->LibPaie = $type->LibPaie;
            $this->editMode = true;
        }
    }

    public function confirmDeleteTypePaiement($id)
    {
        $type = RefTypePaiement::find($id);
        if ($type) {
            $this->typeToDeleteId  = $id;
            $this->typeToDeleteNom = $type->LibPaie;
            $this->showDeleteConfirm = true;
        }
    }

    public function deleteTypePaiement()
    {
        $type = RefTypePaiement::find($this->typeToDeleteId);
        if ($type) {
            $type->delete();
            $this->resetForm();
            $this->resetPage();
        }
        $this->showDeleteConfirm = false;
        $this->typeToDeleteId    = null;
        $this->typeToDeleteNom   = '';
    }

    public function render()
    {
        $types = RefTypePaiement::orderByDesc('idtypepaie')->paginate(5);
        return view('livewire.type-paiement-manager', compact('types'));
    }
}
