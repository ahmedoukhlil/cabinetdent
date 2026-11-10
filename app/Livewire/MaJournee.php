<?php

namespace App\Livewire;

use App\Enums\StatutRdv;
use App\Livewire\Concerns\ChangeStatutRdv;
use App\Models\RendezVous;
use Livewire\Component;

/**
 * Bandeau « Ma journée » des dentistes : patient au fauteuil, salle d'attente, prochain RDV.
 * Le propriétaire peut basculer entre ses données et celles du cabinet, et voit une synthèse.
 */
class MaJournee extends Component
{
    use ChangeStatutRdv;

    public string $portee = 'moi';

    public function setPortee(string $portee): void
    {
        $this->portee = ($portee === 'cabinet' && auth()->user()->estProprietaire()) ? 'cabinet' : 'moi';
    }

    private function requete()
    {
        $query = RendezVous::with(['patient', 'dentiste', 'facture'])->duJour();

        return $this->portee === 'moi' ? $query->where('dentiste_id', auth()->id()) : $query;
    }

    public function render()
    {
        $user = auth()->user();
        $rdvs = $this->requete()->orderBy('heure')->get();
        $maintenant = now()->format('H:i:s');

        $synthese = null;
        if ($user->can('synthese.view')) {
            $synthese = [
                'termines' => $rdvs->where('statut', StatutRdv::Termine)->count(),
                'restants' => $rdvs->whereIn('statut', [StatutRdv::Planifie, StatutRdv::Arrive])->count(),
                'annules' => $rdvs->where('statut', StatutRdv::Annule)->count(),
                'demain' => $this->demain(),
                'recette' => $this->filtrer(\App\Models\Paiement::whereDate('created_at', today()))->get()->sum('montant_signe'),
                'impayes' => $this->filtrer(\App\Models\Facture::impayees())->get()->sum(fn ($f) => max(0, $f->restePatient()) + max(0, $f->restePec())),
            ];
        }

        return view('livewire.ma-journee', [
            'user' => $user,
            'nbRdv' => $rdvs->where('statut', '!=', StatutRdv::Annule)->count(),
            'enCours' => $rdvs->first(fn ($r) => $r->statut === StatutRdv::EnCours && $r->dentiste_id === $user->id)
                ?? ($this->portee === 'cabinet' ? $rdvs->firstWhere('statut', StatutRdv::EnCours) : null),
            'attente' => $rdvs->where('statut', StatutRdv::Arrive)->sortBy('arrive_at')->values(),
            'prochain' => $rdvs->first(fn ($r) => $r->statut === StatutRdv::Planifie && $r->heure >= $maintenant),
            'synthese' => $synthese,
        ]);
    }

    private function filtrer($query)
    {
        return $this->portee === 'moi' ? $query->where('dentiste_id', auth()->id()) : $query;
    }

    private function demain(): int
    {
        $query = RendezVous::query()->duJour(today()->addDay())->actifs();

        return ($this->portee === 'moi' ? $query->where('dentiste_id', auth()->id()) : $query)->count();
    }
}
