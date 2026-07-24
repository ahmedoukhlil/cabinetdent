<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Acte;
use App\Models\Typeacte;
use App\Models\Assureur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActeCreate extends Component
{
    public $Acte, $PrixRef, $fkidTypeActe, $ActeArab, $fkidassureur, $Masquer = 0;
    public $types = [];
    public $assureurs = [];

    protected $rules = [
        'Acte' => 'required|string|max:255',
        'PrixRef' => 'required|numeric|min:0',
        'fkidTypeActe' => 'required|integer|exists:typeactes,ID',
        'ActeArab' => 'nullable|string|max:255',
        'fkidassureur' => 'nullable|integer|exists:assureur,ID',
        'Masquer' => 'boolean',
    ];

    public function mount()
    {
        // Récupérer les types d'actes depuis la table typeactes
        $this->types = DB::table('typeactes')
            ->select('ID', 'Type as LibTypeActe')
            ->where('ISvisible', 1)
            ->orderBy('NumeroOrdre')
            ->get();
            
        $this->assureurs = Assureur::all();
    }

    public function save()
    {
        $this->validate();
        $maxNordre = Acte::max('nordre') ?? 0;
        $nordre = $maxNordre + 1;
        Acte::create([
            'Acte' => $this->Acte,
            'PrixRef' => $this->PrixRef,
            'fkidTypeActe' => $this->fkidTypeActe,
            'nordre' => $nordre,
            'user' => Auth::user()->name ?? 'system',
            'fkidassureur' => $this->fkidassureur,
            'ActeArab' => $this->ActeArab,
            'Masquer' => $this->Masquer ? 1 : 0,
        ]);
        session()->flash('success', 'Acte créé avec succès !');
        $this->reset(['Acte', 'PrixRef', 'fkidTypeActe', 'ActeArab', 'fkidassureur', 'Masquer']);
    }

    public function render()
    {
        return view('livewire.acte-create');
    }
} 