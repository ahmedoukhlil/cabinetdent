<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class LotMedicament
 * 
 * @property int $idLot
 * @property int $fkidStock
 * @property int $fkidMedicament
 * @property string|null $numeroLot
 * @property float $quantiteInitiale
 * @property float $quantiteRestante
 * @property \DateTime|null $dateExpiration
 * @property \DateTime|null $dateEntree
 * @property float $prixAchatUnitaire
 * @property string|null $fournisseur
 * @property string|null $referenceFacture
 * @property int $fkidUser
 * @property int $Masquer
 *
 * @package App\Models
 */
class LotMedicament extends Model
{
    protected $table = 'lots_medicaments';
    protected $primaryKey = 'idLot';
    public $timestamps = false;

    protected $casts = [
        'fkidStock' => 'int',
        'fkidMedicament' => 'int',
        'quantiteInitiale' => 'float',
        'quantiteRestante' => 'float',
        'dateExpiration' => 'date',
        'dateEntree' => 'datetime',
        'prixAchatUnitaire' => 'float',
        'fkidUser' => 'int',
        'Masquer' => 'int'
    ];

    protected $fillable = [
        'fkidStock',
        'fkidMedicament',
        'numeroLot',
        'quantiteInitiale',
        'quantiteRestante',
        'dateExpiration',
        'dateEntree',
        'prixAchatUnitaire',
        'fournisseur',
        'referenceFacture',
        'fkidUser',
        'Masquer'
    ];

    // Relations
    public function stock()
    {
        return $this->belongsTo(StockMedicament::class, 'fkidStock', 'idStock');
    }

    public function medicament()
    {
        return $this->belongsTo(Medicament::class, 'fkidMedicament', 'IDMedic');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkidUser', 'id');
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'fkidLot', 'idLot');
    }

    // Méthodes utilitaires
    /**
     * Vérifie si le lot est expiré
     */
    public function isExpire()
    {
        if (!$this->dateExpiration) {
            return false;
        }
        return Carbon::parse($this->dateExpiration)->isPast();
    }

    /**
     * Vérifie si le lot expire bientôt (dans les 30 jours)
     */
    public function expireBientot($jours = 30)
    {
        if (!$this->dateExpiration) {
            return false;
        }
        $dateExpiration = Carbon::parse($this->dateExpiration);
        $dateAlerte = Carbon::now()->addDays($jours);
        return $dateExpiration->isBefore($dateAlerte) && !$this->isExpire();
    }

    /**
     * Obtient le nombre de jours avant expiration
     */
    public function getJoursAvantExpiration()
    {
        if (!$this->dateExpiration) {
            return null;
        }
        return Carbon::now()->diffInDays(Carbon::parse($this->dateExpiration), false);
    }

    /**
     * Scope pour les lots actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('Masquer', 0)
            ->where('quantiteRestante', '>', 0);
    }

    /**
     * Scope pour les lots expirés
     */
    public function scopeExpires($query)
    {
        return $query->whereNotNull('dateExpiration')
            ->where('dateExpiration', '<', Carbon::now())
            ->where('quantiteRestante', '>', 0);
    }

    /**
     * Scope pour les lots qui expirent bientôt
     */
    public function scopeExpireBientot($query, $jours = 30)
    {
        $dateLimite = Carbon::now()->addDays($jours);
        return $query->whereNotNull('dateExpiration')
            ->where('dateExpiration', '>=', Carbon::now())
            ->where('dateExpiration', '<=', $dateLimite)
            ->where('quantiteRestante', '>', 0);
    }
}

