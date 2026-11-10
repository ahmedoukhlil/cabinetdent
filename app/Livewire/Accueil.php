<?php

namespace App\Livewire;

use App\Enums\StatutRdv;
use App\Models\Patient;
use App\Models\RendezVous;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Accueil')]
class Accueil extends Component
{
    public string $recherche = '';

    public function render()
    {
        $user = auth()->user();

        $resultats = (mb_strlen(trim($this->recherche)) >= 2 && $user->can('patient.view'))
            ? Patient::recherche($this->recherche)->where('actif', true)->orderBy('nom')->limit(6)->get()
            : collect();

        $jour = $user->estDentiste() ? null : RendezVous::duJour()->get()->countBy(fn ($r) => $r->statut->value);

        return view('livewire.accueil', compact('user', 'resultats', 'jour') + ['consultationsPossibles' => $user->can('facture.edit') && \App\Models\Acte::consultations()->exists()]);
    }
}
