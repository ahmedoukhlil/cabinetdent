<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanTraitementLigne extends Model
{
    protected $table = 'plan_traitement_lignes';

    protected $fillable = ['plan_traitement_id', 'acte_id', 'dents', 'libelle', 'quantite', 'prix_unitaire', 'statut', 'realise_at', 'realise_par', 'position'];

    protected $casts = ['dents' => 'array', 'quantite' => 'float', 'prix_unitaire' => 'float', 'realise_at' => 'datetime'];

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlanTraitement::class, 'plan_traitement_id');
    }

    public function factureLigne(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FactureLigne::class, 'plan_ligne_id');
    }

    public function montant(): float
    {
        return round($this->quantite * $this->prix_unitaire, 2);
    }
}
