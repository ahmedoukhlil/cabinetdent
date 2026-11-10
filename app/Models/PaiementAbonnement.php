<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementAbonnement extends Model
{
    protected $table = 'paiements_abonnement';

    protected $fillable = ['abonnement_id', 'montant', 'mois', 'periode_debut', 'periode_fin', 'mode', 'reference', 'admin_id'];

    protected $casts = ['montant' => 'float', 'mois' => 'integer', 'periode_debut' => 'date', 'periode_fin' => 'date'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminPlateforme::class, 'admin_id');
    }
}
