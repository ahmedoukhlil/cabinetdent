<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Assureur;
use Illuminate\Support\Facades\Auth;

class AssureurCreate extends Component
{
    public $LibAssurance, $TauxdePEC, $DtConvention;

    protected $rules = [
        'LibAssurance' => 'required|string|max:255',
        'TauxdePEC' => 'required|numeric|min:0|max:100',
        'DtConvention' => 'required|date',
    ];

    public function save()
    {
        $this->validate();

        Assureur::create([
            'LibAssurance' => $this->LibAssurance,
            'TauxdePEC' => $this->TauxdePEC,
            'DtConvention' => $this->DtConvention,
            'user' => Auth::user()->name ?? 'system',
        ]);

        session()->flash('success', 'Assureur créé avec succès !');
        $this->reset(['LibAssurance', 'TauxdePEC', 'DtConvention']);
    }

    public function render()
    {
        return view('livewire.assureur-create');
    }
} 