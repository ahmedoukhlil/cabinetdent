<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Abonnement d'un cabinet (documentation §9.3). Non soumis au scope cabinet : géré par la plateforme. */
class Abonnement extends Model
{
    public const STATUTS = [
        'essai' => ['Essai', 'bg-sky-100 text-sky-800'],
        'actif' => ['Actif', 'bg-emerald-100 text-emerald-800'],
        'impaye' => ['Impayé', 'bg-amber-100 text-amber-800'],
        'suspendu' => ['Suspendu', 'bg-red-100 text-red-800'],
        'resilie' => ['Résilié', 'bg-slate-200 text-slate-700'],
    ];

    /** Statuts qui bloquent l'accès des utilisateurs du cabinet. */
    public const BLOQUANTS = ['suspendu', 'resilie'];

    protected $fillable = ['cabinet_id', 'plan_id', 'statut', 'essai_fin', 'periode_fin', 'impaye_depuis', 'suspendu_at', 'resilie_at', 'motif_resiliation'];

    protected $casts = ['essai_fin' => 'date', 'periode_fin' => 'date', 'impaye_depuis' => 'date', 'suspendu_at' => 'datetime', 'resilie_at' => 'datetime'];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanAbonnement::class, 'plan_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAbonnement::class)->latest('id');
    }

    /** Date à laquelle l'accès est (ou était) payé ou couvert par l'essai. */
    public function finCouverture(): ?\Carbon\CarbonInterface
    {
        return collect([$this->essai_fin, $this->periode_fin])->filter()->max();
    }

    /**
     * Échéance à surveiller pour le bandeau d'alerte : fin d'essai, fin de période payée
     * ou date de suspension (impayé). Null si rien n'arrive dans les jours d'alerte.
     *
     * @return array{type: string, date: \Carbon\CarbonInterface, jours: int}|null
     */
    public function echeanceProche(?\Carbon\CarbonInterface $aujourdhui = null): ?array
    {
        $jour = ($aujourdhui ?? today())->copy()->startOfDay();
        [$type, $date] = match ($this->statut) {
            'essai' => ['essai', $this->essai_fin],
            'actif' => ['periode', $this->periode_fin],
            'impaye' => ['suspension', $this->finGrace()?->copy()->addDay()],
            default => [null, null],
        };
        if (!$date) {
            return null;
        }
        $jours = (int) $jour->diffInDays($date->copy()->startOfDay(), false);
        if ($type !== 'suspension' && $jours > (int) config('abonnement.alerte_jours')) {
            return null;
        }

        return ['type' => $type, 'date' => $date, 'jours' => max(0, $jours)];
    }

    public function finGrace(): ?\Carbon\CarbonInterface
    {
        return $this->impaye_depuis?->copy()->addDays((int) config('abonnement.grace_jours'));
    }
}
