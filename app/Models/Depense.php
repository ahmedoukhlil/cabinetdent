<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depense extends Model
{
    use BelongsToCabinet, SoftDeletes;

    public const CATEGORIES = [
        'fournitures' => 'Fournitures et consommables',
        'prothesiste' => 'Prothésiste / laboratoire',
        'loyer' => 'Loyer et charges',
        'salaires' => 'Salaires',
        'materiel' => 'Matériel et maintenance',
        'divers' => 'Divers',
    ];

    protected $fillable = ['cabinet_id', 'date_depense', 'categorie', 'libelle', 'montant', 'mode', 'reference', 'dentiste_id', 'user_id', 'motif_suppression', 'supprime_par'];

    protected $casts = ['date_depense' => 'date', 'montant' => 'float'];

    public function dentiste(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentiste_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
