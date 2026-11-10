<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureLigne extends Model
{
    protected $table = 'facture_lignes';

    protected $fillable = ['facture_id', 'acte_id', 'plan_ligne_id', 'libelle', 'dents', 'quantite', 'prix_unitaire', 'montant'];

    protected $casts = ['dents' => 'array', 'quantite' => 'float', 'prix_unitaire' => 'float', 'montant' => 'float'];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }
}
