<?php

namespace App\Services;

use App\Enums\StatutRdv;
use App\Models\Acte;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * La consultation est l'événement déclencheur le plus fréquent : en une action, elle crée
 * une facture propre au rendez-vous (prix selon l'assurance du patient), la valide et,
 * si demandé, encaisse la part patient, qui apparaît aussitôt dans la caisse.
 */
class ConsultationService
{
    public function __construct(private FacturationService $facturation) {}

    /**
     * @param  array{acte_id: int|string, encaisser?: bool, mode?: string, reference?: ?string}  $donnees
     * @return array{facture: Facture, paiement: ?Paiement}
     */
    public function facturer(User $auteur, RendezVous $rdv, array $donnees): array
    {
        if (!$auteur->can('facture.edit')) {
            throw new AuthorizationException('Vous ne pouvez pas facturer.');
        }
        $encaisser = (bool) ($donnees['encaisser'] ?? false);
        if ($encaisser && !$auteur->can('paiement.create')) {
            throw new AuthorizationException('Vous ne pouvez pas encaisser : la facture peut être créée sans encaissement.');
        }

        $donnees = Validator::make($donnees, [
            'acte_id' => ['required', 'integer'],
            'mode' => [$encaisser ? 'required' : 'nullable', Rule::in(array_keys(\App\Models\ModePaiement::actifs($auteur->cabinet_id)))],
            'reference' => ['nullable', 'string', 'max:100'],
        ], [], ['acte_id' => 'consultation', 'mode' => 'mode de paiement'])->validate();

        $acte = Acte::consultations()->find($donnees['acte_id']);
        if (!$acte) {
            throw ValidationException::withMessages(['acte_id' => 'Choisissez un acte de type consultation.']);
        }

        return DB::transaction(function () use ($auteur, $rdv, $acte, $donnees, $encaisser) {
            $rdv = RendezVous::with('patient', 'facture')->whereKey($rdv->id)->lockForUpdate()->firstOrFail();

            if ($rdv->statut === StatutRdv::Annule) {
                throw ValidationException::withMessages(['acte_id' => 'Ce rendez-vous est annulé.']);
            }
            if ($rdv->consultationFacturee()) {
                throw ValidationException::withMessages(['acte_id' => 'La consultation de ce rendez-vous est déjà facturée (facture '.$rdv->facture->numero.').']);
            }

            // Facture dédiée : le devis en cours du patient (plan de traitement) n'est pas touché.
            $facture = Facture::create([
                'cabinet_id' => $rdv->cabinet_id,
                'patient_id' => $rdv->patient_id,
                'dentiste_id' => $rdv->dentiste_id,
                'statut' => 'brouillon',
                'assureur_id' => $rdv->patient->assureur_id,
                'cree_par' => $auteur->id,
            ]);
            $this->facturation->ajouterLigne($auteur, $facture, ['acte_id' => $acte->id, 'quantite' => 1]);
            $facture = $this->facturation->valider($auteur, $facture);
            $rdv->update(['facture_id' => $facture->id]);

            $paiement = null;
            if ($encaisser && $facture->restePatient() > 0) {
                $paiement = $this->facturation->enregistrerPaiement($auteur, $facture, [
                    'payeur' => 'patient', 'type' => 'encaissement', 'mode' => $donnees['mode'],
                    'montant' => $facture->restePatient(), 'reference' => $donnees['reference'] ?? null,
                ]);
            }

            return ['facture' => $facture->fresh(), 'paiement' => $paiement];
        });
    }
}
