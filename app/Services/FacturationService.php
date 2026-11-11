<?php

namespace App\Services;

use App\Models\Acte;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\PlanTraitement;
use App\Models\PlanTraitementLigne;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Règles de facturation : devis (brouillon) modifiable, facture validée et numérotée,
 * répartition patient / assureur, encaissements et remboursements sans trop-perçu.
 */
class FacturationService
{
    /** Devis ouvert du patient, créé s'il n'existe pas. */
    public function brouillonPour(User $auteur, Patient $patient): Facture
    {
        $this->autoriser($auteur, 'facture.edit', $patient->cabinet_id);

        return Facture::where('patient_id', $patient->id)->where('statut', 'brouillon')->latest('id')->first()
            ?? Facture::create([
                'cabinet_id' => $patient->cabinet_id,
                'patient_id' => $patient->id,
                // Praticien : l'auteur s'il est dentiste, sinon celui du dernier rendez-vous du patient.
                'dentiste_id' => $auteur->estDentiste() ? $auteur->id : \App\Models\RendezVous::where('patient_id', $patient->id)
                    ->whereDate('date', '<=', today())->latest('date')->latest('heure')->value('dentiste_id'),
                'statut' => 'brouillon',
                'assureur_id' => $patient->assureur_id,
                'cree_par' => $auteur->id,
            ]);
    }

    public function ajouterLigne(User $auteur, Facture $facture, array $donnees): FactureLigne
    {
        $this->verifierBrouillon($auteur, $facture);

        $donnees = Validator::make($donnees, [
            'acte_id' => ['nullable', 'integer'],
            'libelle' => ['required_without:acte_id', 'nullable', 'string', 'max:150'],
            'quantite' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'prix_unitaire' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'dents' => ['array'],
        ], [], ['libelle' => 'acte', 'prix_unitaire' => 'prix'])->validate();

        $acte = !empty($donnees['acte_id']) ? Acte::find($donnees['acte_id']) : null;
        if (!empty($donnees['acte_id']) && !$acte) {
            throw ValidationException::withMessages(['acte_id' => 'Acte introuvable dans ce cabinet.']);
        }

        return DB::transaction(function () use ($facture, $donnees, $acte) {
            $quantite = round((float) $donnees['quantite'], 2);
            $prix = round((float) ($donnees['prix_unitaire'] ?? $acte?->prixPour($facture->assureur_id ?? $facture->patient?->assureur_id) ?? 0), 2);

            $ligne = $facture->lignes()->create([
                'acte_id' => $acte?->id,
                'libelle' => ($donnees['libelle'] ?? '') !== '' ? $donnees['libelle'] : $acte->libelle,
                'dents' => array_values(array_map('intval', $donnees['dents'] ?? [])) ?: null,
                'quantite' => $quantite,
                'prix_unitaire' => $prix,
                'montant' => round($quantite * $prix, 2),
            ]);
            $this->recalculer($facture);

            return $ligne;
        });
    }

    /** Transfère un soin du plan de traitement vers le devis du patient. */
    public function facturerLignePlan(User $auteur, PlanTraitementLigne $planLigne): FactureLigne
    {
        $plan = PlanTraitement::with('patient')->find($planLigne->plan_traitement_id);
        if (!$plan) {
            throw ValidationException::withMessages(['ligne' => 'Soin introuvable.']);
        }
        $this->autoriser($auteur, 'facture.edit', $plan->cabinet_id);

        if (!in_array($plan->statut, ['accepte', 'en_cours', 'termine'], true)) {
            throw ValidationException::withMessages(['ligne' => 'Le plan doit être accepté par le patient avant facturation.']);
        }

        return DB::transaction(function () use ($auteur, $plan, $planLigne) {
            $dejaFacture = FactureLigne::where('plan_ligne_id', $planLigne->id)
                ->whereHas('facture', fn ($q) => $q->where('statut', '!=', 'annulee'))
                ->lockForUpdate()->exists();
            if ($dejaFacture) {
                throw ValidationException::withMessages(['ligne' => 'Ce soin est déjà facturé.']);
            }

            $facture = $this->brouillonPour($auteur, $plan->patient);
            $ligne = $facture->lignes()->create([
                'plan_ligne_id' => $planLigne->id,
                'acte_id' => $planLigne->acte_id,
                'libelle' => $planLigne->libelle,
                'dents' => $planLigne->dents,
                'quantite' => $planLigne->quantite,
                'prix_unitaire' => $planLigne->prix_unitaire,
                'montant' => $planLigne->montant(),
            ]);
            $this->recalculer($facture);

            return $ligne;
        });
    }

    public function supprimerLigne(User $auteur, FactureLigne $ligne): void
    {
        $facture = Facture::findOrFail($ligne->facture_id);
        $this->verifierBrouillon($auteur, $facture);

        DB::transaction(function () use ($ligne, $facture) {
            $ligne->delete();
            $this->recalculer($facture);
        });
    }

    /** Recalcule les totaux d'un devis à partir de ses lignes et de l'assurance du patient. */
    public function recalculer(Facture $facture): void
    {
        if ($facture->statut === 'brouillon') {
            $assureur = $facture->patient()->first()?->assureur()->where('actif', true)->first();
            $facture->assureur_id = $assureur?->id;
            $facture->taux_pec = $assureur?->taux_pec ?? 0;
        }

        $facture->total = round((float) $facture->lignes()->sum('montant'), 2);
        $facture->part_pec = round($facture->total * $facture->taux_pec, 2);
        $facture->part_patient = round($facture->total - $facture->part_pec, 2);
        $facture->save();
    }

    /** Valide le devis : le montant est figé et un numéro séquentiel par cabinet et par année est attribué. */
    public function valider(User $auteur, Facture $facture): Facture
    {
        $this->verifierBrouillon($auteur, $facture);

        return DB::transaction(function () use ($facture) {
            $facture = Facture::whereKey($facture->id)->lockForUpdate()->firstOrFail();
            $this->recalculer($facture);

            if ($facture->lignes()->doesntExist() || $facture->total <= 0) {
                throw ValidationException::withMessages(['facture' => 'Ajoutez au moins un acte facturable avant de valider.']);
            }

            $annee = (int) now()->format('Y');
            $sequence = (int) Facture::withoutGlobalScopes()
                ->where('cabinet_id', $facture->cabinet_id)->where('annee', $annee)
                ->lockForUpdate()->max('sequence') + 1;

            $facture->update([
                'statut' => 'validee',
                'annee' => $annee,
                'sequence' => $sequence,
                'numero' => $sequence.'-'.$annee,
                'date_facture' => today(),
                'validee_at' => now(),
            ]);

            return $facture;
        });
    }

    /** Supprime un devis, ou annule une facture validée sans paiement net. */
    public function annuler(User $auteur, Facture $facture, ?string $motif = null): void
    {
        if ($facture->statut === 'brouillon') {
            $this->verifierBrouillon($auteur, $facture);
            DB::transaction(fn () => $facture->delete());

            return;
        }

        $this->autoriser($auteur, 'paiement.refund', $facture->cabinet_id);

        if ($facture->statut !== 'validee') {
            throw ValidationException::withMessages(['facture' => 'Cette facture est déjà annulée.']);
        }
        if (round($facture->regle_patient + $facture->regle_pec, 2) > 0) {
            throw ValidationException::withMessages(['facture' => 'Remboursez d’abord les paiements reçus avant d’annuler la facture.']);
        }
        if (trim((string) $motif) === '') {
            throw ValidationException::withMessages(['motif' => 'Indiquez le motif d’annulation.']);
        }

        DB::transaction(function () use ($facture, $motif) {
            $facture->update(['statut' => 'annulee', 'annulee_at' => now(), 'motif_annulation' => $motif]);
            // Les soins du plan redeviennent facturables.
            $facture->lignes()->update(['plan_ligne_id' => null]);
        });
    }

    /**
     * Enregistre un encaissement ou un remboursement.
     *
     * @param  array{payeur: string, type: string, mode: string, montant: float|string, reference?: ?string}  $donnees
     */
    public function enregistrerPaiement(User $auteur, Facture $facture, array $donnees): Paiement
    {
        $donnees = Validator::make($donnees, [
            'payeur' => ['required', Rule::in(['patient', 'assureur'])],
            'type' => ['required', Rule::in(['encaissement', 'remboursement'])],
            'mode' => ['required', Rule::in(array_keys(\App\Models\ModePaiement::actifs($auteur->cabinet_id)))],
            'montant' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'reference' => ['nullable', 'string', 'max:100'],
        ], [], ['montant' => 'montant'])->validate();

        $this->autoriser($auteur, $donnees['type'] === 'remboursement' ? 'paiement.refund' : 'paiement.create', $facture->cabinet_id);

        return DB::transaction(function () use ($auteur, $facture, $donnees) {
            $facture = Facture::whereKey($facture->id)->lockForUpdate()->firstOrFail();

            if ($facture->statut !== 'validee') {
                throw ValidationException::withMessages(['montant' => 'Seule une facture validée peut recevoir un paiement.']);
            }

            $montant = round((float) $donnees['montant'], 2);
            $assureur = $donnees['payeur'] === 'assureur';
            $colonne = $assureur ? 'regle_pec' : 'regle_patient';

            if ($assureur && $facture->part_pec <= 0) {
                throw ValidationException::withMessages(['payeur' => 'Cette facture n’a pas de part assureur.']);
            }

            if ($donnees['type'] === 'encaissement') {
                $reste = $assureur ? $facture->restePec() : $facture->restePatient();
                if ($montant > $reste) {
                    throw ValidationException::withMessages(['montant' => 'Le montant dépasse le reste à payer ('.number_format($reste, 0, ',', ' ').' MRU).']);
                }
                $facture->{$colonne} = round($facture->{$colonne} + $montant, 2);
            } else {
                if ($montant > $facture->{$colonne}) {
                    throw ValidationException::withMessages(['montant' => 'Le remboursement dépasse le montant déjà réglé ('.number_format($facture->{$colonne}, 0, ',', ' ').' MRU).']);
                }
                $facture->{$colonne} = round($facture->{$colonne} - $montant, 2);
            }
            $facture->save();

            return Paiement::create([
                'cabinet_id' => $facture->cabinet_id,
                'facture_id' => $facture->id,
                'patient_id' => $facture->patient_id,
                'dentiste_id' => $facture->dentiste_id,
                'payeur' => $donnees['payeur'],
                'type' => $donnees['type'],
                'mode' => $donnees['mode'],
                'montant' => $montant,
                'reference' => $donnees['reference'] ?? null,
                'user_id' => $auteur->id,
            ]);
        });
    }

    private function verifierBrouillon(User $auteur, Facture $facture): void
    {
        $this->autoriser($auteur, 'facture.edit', $facture->cabinet_id);
        if ($facture->statut !== 'brouillon') {
            throw ValidationException::withMessages(['facture' => 'Une facture validée ne peut plus être modifiée.']);
        }
    }

    private function autoriser(User $auteur, string $permission, int $cabinetId): void
    {
        if ($auteur->cabinet_id !== $cabinetId || !$auteur->aPermission($permission)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
