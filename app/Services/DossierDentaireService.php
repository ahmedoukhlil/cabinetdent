<?php

namespace App\Services;

use App\Models\Dent;
use App\Models\DentHistorique;
use App\Models\Patient;
use App\Models\PlanTraitement;
use App\Models\PlanTraitementLigne;
use App\Models\User;
use App\Support\Odontologie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class DossierDentaireService
{
    /**
     * Enregistre le même état clinique sur une ou plusieurs dents, avec historique.
     *
     * @param  int[]  $numeros
     */
    public function enregistrerDents(User $auteur, Patient $patient, array $numeros, array $etat): void
    {
        $this->verifierPatient($auteur, $patient);
        $numeros = $this->validerDents($numeros, obligatoire: true);

        $etat = Validator::make($etat, [
            'presence' => ['required', Rule::in(array_keys(Odontologie::PRESENCE))],
            'restauration' => ['required', Rule::in(array_keys(Odontologie::RESTAURATION))],
            'endodontie' => ['required', Rule::in(array_keys(Odontologie::ENDODONTIE))],
            'faces' => ['required', 'array'],
            'faces.*' => ['required', Rule::in(array_keys(Odontologie::ETATS_FACE))],
            'constatations' => ['array'],
            'constatations.*' => ['string', Rule::in(Odontologie::codesConstatations())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        if (array_diff(array_keys($etat['faces']), Odontologie::FACES)) {
            throw ValidationException::withMessages(['faces' => 'Face dentaire inconnue.']);
        }

        $etat['faces'] = array_merge(Odontologie::facesSaines(), $etat['faces']);
        $etat['constatations'] = array_values(array_unique($etat['constatations'] ?? []));
        $etat['notes'] = ($etat['notes'] ?? '') === '' ? null : $etat['notes'];

        // Une dent absente ne porte ni faces ni traitement.
        if ($etat['presence'] !== 'presente' && !in_array($etat['restauration'], ['implant', 'couronne_implant', 'pontique'], true)) {
            $etat['faces'] = Odontologie::facesSaines();
            $etat['endodontie'] = 'non_traitee';
        }

        DB::transaction(function () use ($auteur, $patient, $numeros, $etat) {
            foreach ($numeros as $numero) {
                $dent = Dent::withTrashed()->firstOrNew(['patient_id' => $patient->id, 'numero' => $numero]);
                $avant = $dent->exists && !$dent->trashed() ? $dent->etat() : null;

                $dent->fill($etat + ['cabinet_id' => $patient->cabinet_id, 'modifie_par' => $auteur->id]);
                $dent->deleted_at = null;

                if ($avant === $dent->etat()) {
                    continue;
                }

                $dent->save();
                DentHistorique::create([
                    'cabinet_id' => $patient->cabinet_id,
                    'patient_id' => $patient->id,
                    'numero' => $numero,
                    'avant' => $avant,
                    'apres' => $dent->etat(),
                    'auteur_id' => $auteur->id,
                ]);
            }
        });
    }

    public function planActif(Patient $patient): ?PlanTraitement
    {
        return PlanTraitement::where('patient_id', $patient->id)
            ->whereNotIn('statut', ['termine', 'annule'])
            ->latest('id')
            ->first();
    }

    public function ajouterLigne(User $auteur, Patient $patient, array $donnees): PlanTraitementLigne
    {
        $this->verifierPatient($auteur, $patient);

        $donnees = Validator::make($donnees, [
            'libelle' => ['required', 'string', 'max:150'],
            'quantite' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'prix_unitaire' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'dents' => ['array'],
            'acte_id' => ['nullable', 'integer'],
        ], [], ['libelle' => 'acte', 'prix_unitaire' => 'prix'])->validate();

        // L'acte doit appartenir au cabinet (scope) ; sinon la ligne reste un soin libre.
        $donnees['acte_id'] = !empty($donnees['acte_id']) ? \App\Models\Acte::whereKey($donnees['acte_id'])->value('id') : null;

        $donnees['dents'] = $this->validerDents($donnees['dents'] ?? [], obligatoire: false);

        return DB::transaction(function () use ($auteur, $patient, $donnees) {
            $plan = $this->planActif($patient) ?? PlanTraitement::create([
                'cabinet_id' => $patient->cabinet_id,
                'patient_id' => $patient->id,
                'statut' => 'brouillon',
                'cree_par' => $auteur->id,
            ]);

            return $plan->lignes()->create($donnees + [
                'statut' => 'prevu',
                'position' => (int) $plan->lignes()->max('position') + 1,
            ]);
        });
    }

    public function supprimerLigne(User $auteur, PlanTraitementLigne $ligne): void
    {
        $plan = $this->planDeLaLigne($auteur, $ligne);

        if ($ligne->statut === 'realise' || !$plan->estModifiable()) {
            throw ValidationException::withMessages(['ligne' => 'Un soin réalisé ou un plan clôturé ne peut plus être modifié.']);
        }

        if ($ligne->factureLigne()->whereHas('facture', fn ($q) => $q->where('statut', '!=', 'annulee'))->exists()) {
            throw ValidationException::withMessages(['ligne' => 'Ce soin est facturé : retirez-le d’abord du devis.']);
        }

        $ligne->delete();
    }

    public function basculerRealise(User $auteur, PlanTraitementLigne $ligne): void
    {
        $plan = $this->planDeLaLigne($auteur, $ligne);

        if (!in_array($plan->statut, ['accepte', 'en_cours'], true)) {
            throw ValidationException::withMessages(['ligne' => 'Le plan doit être accepté par le patient avant de réaliser des soins.']);
        }

        DB::transaction(function () use ($auteur, $ligne, $plan) {
            $realise = $ligne->statut !== 'realise';
            $ligne->update([
                'statut' => $realise ? 'realise' : 'prevu',
                'realise_at' => $realise ? now() : null,
                'realise_par' => $realise ? $auteur->id : null,
            ]);

            if ($realise && $plan->statut === 'accepte') {
                $plan->update(['statut' => 'en_cours']);
            }
        });
    }

    public function changerStatutPlan(User $auteur, PlanTraitement $plan, string $statut): void
    {
        $this->verifierPatient($auteur, $plan->patient);

        if (!$plan->peutPasserA($statut)) {
            throw ValidationException::withMessages(['plan' => 'Ce changement de statut n’est pas autorisé.']);
        }

        if ($statut === 'propose' && $plan->lignes()->doesntExist()) {
            throw ValidationException::withMessages(['plan' => 'Ajoutez au moins un soin avant de proposer le plan.']);
        }

        if ($statut === 'termine' && $plan->lignes()->where('statut', '!=', 'realise')->exists()) {
            throw ValidationException::withMessages(['plan' => 'Tous les soins doivent être réalisés pour terminer le plan.']);
        }

        $plan->update(['statut' => $statut] + ($statut === 'accepte' ? ['accepte_at' => now()] : []));
    }

    private function planDeLaLigne(User $auteur, PlanTraitementLigne $ligne): PlanTraitement
    {
        $plan = PlanTraitement::with('patient')->find($ligne->plan_traitement_id);
        if (!$plan) {
            throw ValidationException::withMessages(['ligne' => 'Soin introuvable.']);
        }
        $this->verifierPatient($auteur, $plan->patient);

        return $plan;
    }

    private function verifierPatient(User $auteur, ?Patient $patient): void
    {
        if (!$patient || $patient->cabinet_id !== $auteur->cabinet_id || !$auteur->aPermission('dossier.edit')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Action non autorisée sur ce dossier.');
        }
    }

    /** @return int[] */
    private function validerDents(array $numeros, bool $obligatoire): array
    {
        $numeros = array_values(array_unique(array_map('intval', $numeros)));
        sort($numeros);

        if ($obligatoire && $numeros === []) {
            throw ValidationException::withMessages(['dents' => 'Sélectionnez au moins une dent.']);
        }

        foreach ($numeros as $numero) {
            if (!Odontologie::estDentValide($numero)) {
                throw ValidationException::withMessages(['dents' => "Dent {$numero} inconnue (notation FDI)."]);
            }
        }

        return $numeros;
    }
}
