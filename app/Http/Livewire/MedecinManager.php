<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use App\Models\Medecin;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class MedecinManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $medecin_id = null;
    public $Nom = '';
    public $Contact = '';
    public $editMode = false;
    public $showDeleteConfirm = false;
    public $medecinToDeleteId = null;
    public $medecinToDeleteNom = '';

    protected $rules = [
        'Nom' => 'required|string|max:255',
        'Contact' => 'required|string|max:255',
    ];

    public function updating($property)
    {
        if ($property === 'Nom' || $property === 'Contact') {
            $this->resetPage();
        }
    }

    public function resetForm()
    {
        $this->medecin_id = null;
        $this->Nom = '';
        $this->Contact = '';
        $this->editMode = false;
    }

    public function saveMedecin()
    {
        $this->validate();
        if ($this->editMode && $this->medecin_id) {
            $medecin = Medecin::find($this->medecin_id);
            if ($medecin) {
                $medecin->update([
                    'Nom' => $this->Nom,
                    'Contact' => $this->Contact,
                ]);
            }
        } else {
            Medecin::create([
                'Nom' => $this->Nom,
                'Contact' => $this->Contact,
                'DtAjout' => now(),
                'fkidcabinet' => Auth::user()->fkidcabinet ?? 1,
            ]);
        }
        $this->resetForm();
        $this->resetPage();
    }

    public function editMedecin($id)
    {
        $medecin = Medecin::find($id);
        if ($medecin) {
            $this->medecin_id = $medecin->idMedecin;
            $this->Nom = $medecin->Nom;
            $this->Contact = $medecin->Contact;
            $this->editMode = true;
        }
    }

    public function confirmDeleteMedecin($id)
    {
        $medecin = Medecin::find($id);
        if ($medecin) {
            $this->medecinToDeleteId  = $id;
            $this->medecinToDeleteNom = $medecin->Nom;
            $this->showDeleteConfirm  = true;
        }
    }

    public function deleteMedecin()
    {
        $medecin = Medecin::find($this->medecinToDeleteId);
        if ($medecin) {
            $medecin->delete();
            $this->resetForm();
            $this->resetPage();
        }
        $this->showDeleteConfirm  = false;
        $this->medecinToDeleteId  = null;
        $this->medecinToDeleteNom = '';
    }

    public function render()
    {
        $medecins = Medecin::orderByDesc('idMedecin')->paginate(5);
        return view('livewire.medecin-manager', compact('medecins'));
    }
}
