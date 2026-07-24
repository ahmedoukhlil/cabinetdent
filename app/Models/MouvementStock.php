<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class MouvementStock
 * 
 * @property int $idMouvement
 * @property int $fkidStock
 * @property int $fkidMedicament
 * @property int|null $fkidLot
 * @property string $typeMouvement
 * @property float $quantite
 * @property float $prixUnitaire
 * @property float $montantTotal
 * @property string|null $motif
 * @property int|null $fkidFacture
 * @property int|null $fkidDetailFacture
 * @property int|null $fkidPatient
 * @property int $fkidUser
 * @property \DateTime|null $dateMouvement
 * @property string|null $reference
 * @property string|null $notes
 *
 * @package App\Models
 */
class MouvementStock extends Model
{
    protected $table = 'mouvements_stock';
    protected $primaryKey = 'idMouvement';
    public $timestamps = false;

    protected $casts = [
        'fkidStock' => 'int',
        'fkidMedicament' => 'int',
        'fkidLot' => 'int',
        'quantite' => 'float',
        'prixUnitaire' => 'float',
        'montantTotal' => 'float',
        'fkidFacture' => 'int',
        'fkidDetailFacture' => 'int',
        'fkidPatient' => 'int',
        'fkidUser' => 'int',
        'dateMouvement' => 'datetime'
    ];

    protected $fillable = [
        'fkidStock',
        'fkidMedicament',
        'fkidLot',
        'typeMouvement',
        'quantite',
        'prixUnitaire',
        'montantTotal',
        'motif',
        'fkidFacture',
        'fkidDetailFacture',
        'fkidPatient',
        'fkidUser',
        'dateMouvement',
        'reference',
        'notes'
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

    public function lot()
    {
        return $this->belongsTo(LotMedicament::class, 'fkidLot', 'idLot');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'fkidFacture', 'Idfacture');
    }

    public function detailFacture()
    {
        return $this->belongsTo(Detailfacturepatient::class, 'fkidDetailFacture', 'idDetfacture');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkidUser', 'Iduser');
    }

    // Scopes
    public function scopeEntrees($query)
    {
        return $query->where('typeMouvement', 'ENTREE');
    }

    public function scopeSorties($query)
    {
        return $query->where('typeMouvement', 'SORTIE');
    }

    public function scopeAjustements($query)
    {
        return $query->where('typeMouvement', 'AJUSTEMENT');
    }

    public function scopeParPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('dateMouvement', [$dateDebut, $dateFin]);
    }

    public function scopeParMedicament($query, $medicamentId)
    {
        return $query->where('fkidMedicament', $medicamentId);
    }
}

