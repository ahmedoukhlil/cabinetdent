<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepenseService
{
    public function enregistrer(User $auteur, array $donnees): Depense
    {
        $this->exiger($auteur, 'depense.create');
        $d = Validator::make($donnees, [
            'date_depense' => ['required', 'date', 'before_or_equal:today'],
            'categorie' => ['required', Rule::in(array_keys(Depense::CATEGORIES))],
            'libelle' => ['required', 'string', 'max:150'],
            'montant' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'mode' => ['required', Rule::in(array_keys(Paiement::MODES))],
            'reference' => ['nullable', 'string', 'max:100'],
            'dentiste_id' => ['nullable', 'integer'],
        ], [], ['date_depense' => 'date', 'libelle' => 'libellé'])->validate();

        if (!empty($d['dentiste_id']) && !User::dentistesDu($auteur)->whereKey($d['dentiste_id'])->exists()) {
            throw ValidationException::withMessages(['dentiste_id' => 'Dentiste introuvable dans ce cabinet.']);
        }

        return Depense::create([
            ...$d,
            'montant' => round((float) $d['montant'], 2),
            'dentiste_id' => $d['dentiste_id'] ?: null,
            'cabinet_id' => $auteur->cabinet_id,
            'user_id' => $auteur->id,
        ]);
    }

    /** Suppression logique avec motif, réservée au propriétaire : la dépense reste tracée. */
    public function supprimer(User $auteur, Depense $depense, string $motif): void
    {
        $this->exiger($auteur, 'depense.delete');
        if (trim($motif) === '') {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire.']);
        }
        $depense->update(['motif_suppression' => mb_substr(trim($motif), 0, 255), 'supprime_par' => $auteur->id]);
        $depense->delete();
    }

    private function exiger(User $user, string $permission): void
    {
        if (!$user->aPermission($permission)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
