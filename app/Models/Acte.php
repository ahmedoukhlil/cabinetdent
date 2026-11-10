<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acte extends Model
{
    use BelongsToCabinet;

    protected $fillable = ['cabinet_id', 'code', 'libelle', 'type', 'prix', 'actif'];

    public const TYPES = ['soin' => 'Soin', 'consultation' => 'Consultation'];

    protected $attributes = ['type' => 'soin'];

    protected $casts = ['prix' => 'float', 'actif' => 'boolean'];

    public function estConsultation(): bool
    {
        return $this->type === 'consultation';
    }

    public function scopeConsultations($query)
    {
        return $query->where('type', 'consultation')->where('actif', true);
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(ActeTarif::class);
    }

    /** Prix pour un assureur donné ; à défaut, prix sans assurance. */
    public function prixPour(?int $assureurId): float
    {
        if (!$assureurId) {
            return $this->prix;
        }
        $tarif = $this->relationLoaded('tarifs')
            ? $this->tarifs->firstWhere('assureur_id', $assureurId)
            : $this->tarifs()->where('assureur_id', $assureurId)->first();

        return $tarif?->prix ?? $this->prix;
    }
}
