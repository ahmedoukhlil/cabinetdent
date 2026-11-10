<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\AdminPlateforme;
use App\Models\Cabinet;
use App\Models\FichierPatient;
use App\Models\PaiementAbonnement;
use App\Models\Paiement;
use App\Models\PlanAbonnement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Abonnements SaaS (documentation §9.3, §10.12, §10.13).
 *
 * essai ──paiement──▶ actif ──période expirée──▶ impayé ──grâce expirée──▶ suspendu
 *   └──essai expiré──▶ impayé ◀───────────────────┘  └──paiement──▶ actif ◀──paiement──┘
 * Résilié est final : reprendre exige un nouvel abonnement.
 */
class AbonnementService
{
    public function demarrerEssai(Cabinet $cabinet, PlanAbonnement $plan, ?int $jours = null): Abonnement
    {
        $courant = $cabinet->abonnement()->first();
        if ($courant && $courant->statut !== 'resilie') {
            throw ValidationException::withMessages(['plan_id' => 'Ce cabinet a déjà un abonnement en cours.']);
        }

        return Abonnement::create([
            'cabinet_id' => $cabinet->id,
            'plan_id' => $plan->id,
            'statut' => 'essai',
            'essai_fin' => today()->addDays($jours ?? (int) config('abonnement.essai_jours')),
        ]);
    }

    /**
     * Base = date la plus tardive parmi aujourd'hui, fin de période et fin d'essai ;
     * NouvelleFin = Base + mois couverts ; statut actif.
     */
    public function enregistrerPaiement(AdminPlateforme $admin, Abonnement $abonnement, array $donnees): PaiementAbonnement
    {
        if ($abonnement->statut === 'resilie') {
            throw ValidationException::withMessages(['mois' => 'Abonnement résilié : créez un nouvel abonnement.']);
        }
        $d = Validator::make($donnees, [
            'mois' => ['required', 'integer', 'min:1', 'max:36'],
            'montant' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'mode' => ['required', Rule::in(array_keys(Paiement::MODES))],
            'reference' => ['nullable', 'string', 'max:100'],
        ])->validate();

        return DB::transaction(function () use ($admin, $abonnement, $d) {
            $a = Abonnement::lockForUpdate()->findOrFail($abonnement->id);
            $base = collect([today(), $a->periode_fin, $a->essai_fin])->filter()->max()->copy();
            $fin = $base->copy()->addMonthsNoOverflow((int) $d['mois']);

            $a->update(['statut' => 'actif', 'periode_fin' => $fin, 'impaye_depuis' => null, 'suspendu_at' => null]);

            return PaiementAbonnement::create([
                'abonnement_id' => $a->id,
                'montant' => round((float) ($d['montant'] ?? $a->plan->prix_mensuel * $d['mois']), 2),
                'mois' => (int) $d['mois'],
                'periode_debut' => $base,
                'periode_fin' => $fin,
                'mode' => $d['mode'],
                'reference' => $d['reference'] ?? null,
                'admin_id' => $admin->id,
            ]);
        });
    }

    /** Applique les échéances : essai ou période expirés → impayé ; grâce expirée → suspendu. */
    public function verifier(Abonnement $a, ?CarbonInterface $aujourdhui = null): Abonnement
    {
        $jour = ($aujourdhui ?? today())->copy()->startOfDay();

        if ($a->statut === 'essai' && $a->essai_fin && $a->essai_fin->lt($jour)) {
            $a->fill(['statut' => 'impaye', 'impaye_depuis' => $a->essai_fin->copy()->addDay()]);
        }
        if ($a->statut === 'actif' && $a->periode_fin && $a->periode_fin->lt($jour)) {
            $a->fill(['statut' => 'impaye', 'impaye_depuis' => $a->periode_fin->copy()->addDay()]);
        }
        if ($a->statut === 'impaye' && $a->finGrace()?->lt($jour)) {
            $a->fill(['statut' => 'suspendu', 'suspendu_at' => now()]);
        }
        if ($a->isDirty()) {
            $a->save();
        }

        return $a;
    }

    /** @return int nombre d'abonnements dont le statut a changé */
    public function verifierTous(?CarbonInterface $aujourdhui = null): int
    {
        $changes = 0;
        Abonnement::whereIn('statut', ['essai', 'actif', 'impaye'])->each(function ($a) use ($aujourdhui, &$changes) {
            $avant = $a->statut;
            if ($this->verifier($a, $aujourdhui)->statut !== $avant) {
                $changes++;
            }
        });

        return $changes;
    }

    /** Résiliation : état final, motif obligatoire. */
    public function resilier(Abonnement $a, string $motif): Abonnement
    {
        if ($a->statut === 'resilie') {
            throw ValidationException::withMessages(['motif' => 'Abonnement déjà résilié.']);
        }
        if (trim($motif) === '') {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire.']);
        }
        $a->update(['statut' => 'resilie', 'resilie_at' => now(), 'motif_resiliation' => mb_substr(trim($motif), 0, 255)]);

        return $a;
    }

    public function changerPlan(Abonnement $a, PlanAbonnement $plan): Abonnement
    {
        if ($a->statut === 'resilie') {
            throw ValidationException::withMessages(['plan_id' => 'Abonnement résilié.']);
        }
        $a->update(['plan_id' => $plan->id]);

        return $a;
    }

    /** Un cabinet sans abonnement (installation autonome) n'est pas limité. */
    public static function accesAutorise(Cabinet $cabinet): bool
    {
        $a = $cabinet->abonnement;

        return $cabinet->actif && (!$a || !in_array($a->statut, Abonnement::BLOQUANTS, true));
    }

    // --- Quotas (documentation §10.12) -------------------------------------------------

    public static function verifierQuotaUtilisateurs(Cabinet $cabinet): void
    {
        $max = $cabinet->abonnement?->plan?->max_utilisateurs;
        if ($max !== null && User::where('cabinet_id', $cabinet->id)->where('actif', true)->count() >= $max) {
            throw ValidationException::withMessages(['email' => "Votre formule est limitée à {$max} comptes actifs. Désactivez un compte ou changez de formule."]);
        }
    }

    /** Utilisé = somme des tailles, suppressions logiques incluses. */
    public static function stockageUtilise(Cabinet $cabinet): int
    {
        return (int) FichierPatient::withoutGlobalScopes()->withTrashed()->where('cabinet_id', $cabinet->id)->sum('taille');
    }

    public static function verifierQuotaStockage(Cabinet $cabinet, int $octets): void
    {
        $max = $cabinet->abonnement?->plan?->max_stockage_mo;
        if ($max === null) {
            return;
        }
        $disponible = max(0, $max * 1024 * 1024 - self::stockageUtilise($cabinet));
        if ($octets > $disponible) {
            throw ValidationException::withMessages(['fichier' => "Espace de stockage de la formule atteint ({$max} Mo). Changez de formule pour ajouter des fichiers."]);
        }
    }
}
