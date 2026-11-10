<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consommable extends Model
{
    use BelongsToCabinet;

    public const CATEGORIES = [
        'protection' => 'Protection et hygiène',
        'anesthesie' => 'Anesthésie',
        'restauration' => 'Restauration (composites, ciments)',
        'endodontie' => 'Endodontie',
        'chirurgie' => 'Chirurgie',
        'empreinte' => 'Empreintes et prothèse',
        'sterilisation' => 'Stérilisation',
        'autre' => 'Autre',
    ];

    public const ALERTES = [
        'rupture' => ['Rupture', 'bg-red-100 text-red-800'],
        'perime' => ['Périmé', 'bg-red-100 text-red-800'],
        'faible' => ['Stock faible', 'bg-amber-100 text-amber-800'],
        'peremption_proche' => ['Péremption proche', 'bg-amber-100 text-amber-800'],
    ];

    protected $table = 'consommables';

    protected $fillable = ['cabinet_id', 'nom', 'categorie', 'unite', 'quantite', 'quantite_min', 'prix_achat', 'peremption', 'actif'];

    protected $casts = ['quantite' => 'float', 'quantite_min' => 'float', 'prix_achat' => 'float', 'peremption' => 'date', 'actif' => 'boolean'];

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * Alertes (documentation §10.11) : rupture si quantité ≤ 0, faible si ≤ minimum,
     * périmé si la date est passée avec du stock restant, proche si elle tombe dans les 30 jours.
     *
     * @return string[]
     */
    public function alertes(): array
    {
        $a = [];
        if ($this->quantite <= 0) {
            $a[] = 'rupture';
        } elseif ($this->quantite <= $this->quantite_min) {
            $a[] = 'faible';
        }
        if ($this->peremption && $this->quantite > 0) {
            if ($this->peremption->lt(today())) {
                $a[] = 'perime';
            } elseif ($this->peremption->lte(today()->addDays(30))) {
                $a[] = 'peremption_proche';
            }
        }

        return $a;
    }

    public function valeur(): float
    {
        return round(max(0, $this->quantite) * $this->prix_achat, 2);
    }
}
