<?php

namespace App\Services;

use App\Models\Consommable;
use App\Models\MouvementStock;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Stock de consommables. La quantité n'est jamais modifiée directement :
 * chaque variation passe par un mouvement tracé (entrée, sortie ou inventaire).
 */
class StockService
{
    public function enregistrerArticle(User $user, array $donnees, ?Consommable $article = null): Consommable
    {
        $this->exiger($user, 'stock.manage');
        $d = Validator::make($donnees, [
            'nom' => ['required', 'string', 'max:150',
                Rule::unique('consommables', 'nom')->where('cabinet_id', $user->cabinet_id)->ignore($article?->id)],
            'categorie' => ['required', Rule::in(array_keys(Consommable::CATEGORIES))],
            'unite' => ['required', 'string', 'max:30'],
            'quantite_min' => ['required', 'numeric', 'min:0', 'max:999999'],
        ], [], ['quantite_min' => 'seuil d\'alerte', 'unite' => 'unité'])->validate();

        $article ??= new Consommable(['cabinet_id' => $user->cabinet_id]);
        $article->fill($d)->save();

        return $article;
    }

    /**
     * Entrée (achat) : NouveauStock = AncienStock + Quantité ; prix d'achat moyen pondéré.
     * Sortie (utilisation) : refusée au-delà du stock disponible.
     * Inventaire : fixe la quantité comptée, l'écart est tracé avec un motif obligatoire.
     */
    public function mouvement(User $user, Consommable $article, array $donnees): MouvementStock
    {
        $this->exiger($user, 'stock.manage');
        $d = Validator::make($donnees, [
            'type' => ['required', Rule::in(array_keys(MouvementStock::TYPES))],
            'quantite' => ['required', 'numeric', (($donnees['type'] ?? '') === 'ajustement') ? 'min:0' : 'gt:0', 'max:999999'],
            'prix_unitaire' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'peremption' => ['nullable', 'date'],
            'motif' => [($donnees['type'] ?? '') === 'ajustement' ? 'required' : 'nullable', 'string', 'max:255'],
        ], ['motif.required' => 'Indiquez le motif de l\'inventaire.'], ['prix_unitaire' => 'prix unitaire', 'peremption' => 'date de péremption'])->validate();

        return DB::transaction(function () use ($user, $article, $d) {
            $a = Consommable::lockForUpdate()->findOrFail($article->id);
            $q = round((float) $d['quantite'], 2);

            [$variation, $nouveau] = match ($d['type']) {
                'entree' => [$q, $a->quantite + $q],
                'sortie' => [-$q, $a->quantite - $q],
                'ajustement' => [round($q - $a->quantite, 2), $q],
            };
            if ($d['type'] === 'sortie' && $nouveau < 0) {
                throw ValidationException::withMessages(['quantite' => "Stock insuffisant : {$this->nombre($a->quantite)} {$a->unite} disponible(s)."]);
            }
            if ($d['type'] === 'ajustement' && $variation == 0.0) {
                throw ValidationException::withMessages(['quantite' => 'La quantité comptée est identique au stock.']);
            }

            if ($d['type'] === 'entree') {
                $prix = isset($d['prix_unitaire']) ? (float) $d['prix_unitaire'] : $a->prix_achat;
                $ancien = max(0, $a->quantite);
                $a->prix_achat = round(($ancien * $a->prix_achat + $q * $prix) / ($ancien + $q), 2);
                if (!empty($d['peremption'])) {
                    // On garde la date la plus proche : c'est le lot à surveiller.
                    $date = \Illuminate\Support\Carbon::parse($d['peremption']);
                    $a->peremption = ($a->quantite > 0 && $a->peremption && $a->peremption->lt($date)) ? $a->peremption : $date;
                }
            }
            $a->quantite = round($nouveau, 2);
            if ($a->quantite <= 0) {
                $a->peremption = null;
            }
            $a->save();

            return MouvementStock::create([
                'cabinet_id' => $a->cabinet_id,
                'consommable_id' => $a->id,
                'type' => $d['type'],
                'quantite' => $variation,
                'stock_apres' => $a->quantite,
                'prix_unitaire' => $d['type'] === 'entree' ? ($d['prix_unitaire'] ?? null) : null,
                'peremption' => $d['type'] === 'entree' ? ($d['peremption'] ?? null) : null,
                'motif' => $d['motif'] ?? null,
                'user_id' => $user->id,
            ]);
        });
    }

    private function nombre(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', ' '), '0'), ',');
    }

    private function exiger(User $user, string $permission): void
    {
        if (!$user->aPermission($permission)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
