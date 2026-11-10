<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanTraitement extends Model
{
    use BelongsToCabinet, SoftDeletes;

    protected $table = 'plans_traitement';

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'propose' => 'Proposé au patient',
        'accepte' => 'Accepté',
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'annule' => 'Annulé',
    ];

    /** Transitions autorisées : l'ordre du cycle de vie est imposé. */
    public const TRANSITIONS = [
        'brouillon' => ['propose', 'annule'],
        'propose' => ['accepte', 'brouillon', 'annule'],
        'accepte' => ['en_cours', 'annule'],
        'en_cours' => ['termine', 'annule'],
        'termine' => [],
        'annule' => [],
    ];

    protected $fillable = ['cabinet_id', 'patient_id', 'titre', 'statut', 'accepte_at', 'cree_par'];

    protected $casts = ['accepte_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(PlanTraitementLigne::class)->orderBy('position')->orderBy('id');
    }

    public function estModifiable(): bool
    {
        return !in_array($this->statut, ['termine', 'annule'], true);
    }

    public function peutPasserA(string $statut): bool
    {
        return in_array($statut, self::TRANSITIONS[$this->statut] ?? [], true);
    }

    public function total(): float
    {
        return round($this->lignes->sum(fn ($l) => $l->montant()), 2);
    }
}
