<?php

namespace App\Livewire;

use App\Enums\StatutRdv;
use App\Livewire\Concerns\ChangeStatutRdv;
use App\Models\RendezVous;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title("Salle d'attente")]
class SalleAttente extends Component
{
    use ChangeStatutRdv;

    public ?int $dentisteId = null;

    public function render()
    {
        $user = auth()->user();

        $rdvs = RendezVous::with(['patient', 'dentiste', 'facture'])
            ->duJour()
            ->visiblesPar($user)
            ->when($this->dentisteId, fn ($q) => $q->where('dentiste_id', $this->dentisteId))
            ->get();

        return view('livewire.salle-attente', [
            'attendus' => $rdvs->where('statut', StatutRdv::Planifie)->sortBy('heure')->values(),
            'arrives' => $rdvs->where('statut', StatutRdv::Arrive)->sortBy('arrive_at')->values(),
            'enSoins' => $rdvs->where('statut', StatutRdv::EnCours)->sortBy('debut_at')->values(),
            'termines' => $rdvs->where('statut', StatutRdv::Termine)->sortByDesc('fin_at')->values(),
            'consultationsPossibles' => \App\Models\Acte::consultations()->exists(),
            'dentistes' => User::dentistesDu($user)->get(),
            'user' => $user,
        ]);
    }
}
