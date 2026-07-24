<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StockMedicament
 * 
 * @property int $idStock
 * @property int $fkidMedicament
 * @property int $fkidCabinet
 * @property float $quantiteStock
 * @property float $quantiteMin
 * @property float $prixAchat
 * @property float $prixVente
 * @property \DateTime|null $dateDerniereEntree
 * @property \DateTime|null $dateDerniereSortie
 * @property int $Masquer
 *
 * @package App\Models
 */
class StockMedicament extends Model
{
    protected $table = 'stock_medicaments';
    protected $primaryKey = 'idStock';
    public $timestamps = false;

    protected $casts = [
        'fkidMedicament' => 'int',
        'fkidCabinet' => 'int',
        'quantiteStock' => 'float',
        'quantiteMin' => 'float',
        'prixAchat' => 'float',
        'prixVente' => 'float',
        'dateDerniereEntree' => 'datetime',
        'dateDerniereSortie' => 'datetime',
        'Masquer' => 'int'
    ];

    protected $fillable = [
        'fkidMedicament',
        'fkidCabinet',
        'quantiteStock',
        'quantiteMin',
        'prixAchat',
        'prixVente',
        'dateDerniereEntree',
        'dateDerniereSortie',
        'Masquer'
    ];

    // Relations
    public function medicament()
    {
        return $this->belongsTo(Medicament::class, 'fkidMedicament', 'IDMedic');
    }

    public function cabinet()
    {
        return $this->belongsTo(Cabinet::class, 'fkidCabinet', 'idCabinet');
    }

    public function lots()
    {
        return $this->hasMany(LotMedicament::class, 'fkidStock', 'idStock')
            ->where('Masquer', 0)
            ->where('quantiteRestante', '>', 0)
            ->orderBy('dateExpiration', 'asc'); // FIFO : First In First Out
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'fkidStock', 'idStock');
    }

    // Méthodes utilitaires
    /**
     * Vérifie si le stock est faible
     */
    public function isStockFaible()
    {
        return $this->quantiteStock <= $this->quantiteMin;
    }

    /**
     * Obtient le stock disponible (en tenant compte des lots)
     */
    public function getStockDisponible()
    {
        return $this->quantiteStock;
    }

    /**
     * Obtient la valeur du stock
     */
    public function getValeurStock()
    {
        return $this->quantiteStock * $this->prixAchat;
    }

    /**
     * Scope pour les stocks actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('Masquer', 0);
    }

    /**
     * Scope pour les stocks faibles
     */
    public function scopeStockFaible($query)
    {
        return $query->whereColumn('quantiteStock', '<=', 'quantiteMin')
            ->where('Masquer', 0);
    }
}

