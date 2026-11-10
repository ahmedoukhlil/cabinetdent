<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementStock extends Model
{
    use BelongsToCabinet;

    public const TYPES = ['entree' => 'Entrée', 'sortie' => 'Sortie', 'ajustement' => 'Inventaire'];

    protected $table = 'mouvements_stock';

    protected $fillable = ['cabinet_id', 'consommable_id', 'type', 'quantite', 'stock_apres', 'prix_unitaire', 'peremption', 'motif', 'user_id'];

    protected $casts = ['quantite' => 'float', 'stock_apres' => 'float', 'prix_unitaire' => 'float', 'peremption' => 'date'];

    public function consommable(): BelongsTo
    {
        return $this->belongsTo(Consommable::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
