<?php

namespace App\Livewire\Concerns;

use App\Enums\StatutRdv;
use App\Models\RendezVous;
use App\Services\RendezVousService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Action partagée par l'accueil et la salle d'attente pour faire avancer un rendez-vous. */
trait ChangeStatutRdv
{
    #[\Livewire\Attributes\On('consultation-facturee')]
    public function rafraichirConsultation(): void {}

    public function changerStatut(int $rdvId, string $statut, RendezVousService $service): void
    {
        $rdv = RendezVous::find($rdvId);
        $cible = StatutRdv::tryFrom($statut);
        if (!$rdv || !$cible) {
            return;
        }

        try {
            $service->changerStatut(auth()->user(), $rdv, $cible);
            $this->dispatch('toast', message: "{$rdv->patient->nom_complet} : {$cible->label()}", type: 'success');
            // L'arrivée déclenche la facturation de la consultation (fenêtre « Consultation rapide »).
            if ($cible === StatutRdv::Arrive && auth()->user()->can('facture.edit') && \App\Models\Acte::consultations()->exists()) {
                $this->dispatch('consultation-ouvrir', rdv: $rdv->id);
            }
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }
}
