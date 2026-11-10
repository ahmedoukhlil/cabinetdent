<?php

namespace App\Livewire;

use App\Enums\StatutRdv;
use App\Models\Acte;
use App\Models\RendezVous;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Alerte de l'accueil : patients vus aujourd'hui dont la consultation n'est pas facturée,
 * ou facturée mais pas encore encaissée. Évite les pertes de recette en fin de journée.
 */
class ConsultationsNonFacturees extends Component
{
    #[On('consultation-facturee')]
    public function rafraichir(): void {}

    public function render()
    {
        $user = auth()->user();
        $lignes = collect();

        if ($user->can('facture.edit') && Acte::consultations()->exists()) {
            $lignes = RendezVous::with(['patient', 'dentiste', 'facture'])
                ->duJour()
                ->whereIn('statut', [StatutRdv::Arrive->value, StatutRdv::EnCours->value, StatutRdv::Termine->value])
                ->when($user->estDentiste() && !$user->aPermission('rendez-vous.edit'), fn ($q) => $q->where('dentiste_id', $user->id))
                ->orderBy('heure')
                ->get()
                ->map(function (RendezVous $rdv) {
                    if (!$rdv->consultationFacturee()) {
                        // Arrivé / en soins sans facture : normal, on ne signale que les patients partis.
                        return $rdv->statut === StatutRdv::Termine ? ['rdv' => $rdv, 'etat' => 'non_facturee', 'reste' => null] : null;
                    }
                    $reste = $rdv->facture->restePatient();

                    return $reste > 0 ? ['rdv' => $rdv, 'etat' => 'a_encaisser', 'reste' => $reste] : null;
                })
                ->filter()
                ->values();
        }

        return view('livewire.consultations-non-facturees', [
            'lignes' => $lignes,
            'peutEncaisser' => $user->can('paiement.create'),
        ]);
    }
}
