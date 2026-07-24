<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Factureaimprimer
 * 
 * @property int $IDDetail
 * @property string|null $Numfacture
 * @property Carbon|null $DtFacture
 * @property string|null $NomPatient
 * @property string|null $Assureur
 * @property string|null $Actes
 * @property string|null $Dents
 * @property string|null $TelephonePatient
 * @property float|null $Qte
 * @property float|null $PU
 * @property float|null $Soustotal
 * @property float|null $TotFacture
 * @property float|null $TotalPEC
 * @property float|null $TotalFactPatient
 * @property string|null $IntituleTotal
 * @property string|null $EnLettre
 * @property Carbon|null $DtNaissance
 * @property string|null $Genre
 * @property float $NumDossierPat
 * @property string|null $Type
 * @property string|null $Medecin
 * @property string|null $TelCabinet
 * @property float|null $TotReglPat
 * @property string $ActesAr
 * @property string $TypeAr
 * @property string $NomAr
 * @property float $remise
 *
 * @package App\Models
 */
class Factureaimprimer extends Model
{
	protected $table = 'factureaimprimer';
	protected $primaryKey = 'IDDetail';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'IDDetail' => 'int',
		'DtFacture' => 'datetime',
		'Qte' => 'float',
		'PU' => 'float',
		'Soustotal' => 'float',
		'TotFacture' => 'float',
		'TotalPEC' => 'float',
		'TotalFactPatient' => 'float',
		'DtNaissance' => 'datetime',
		'NumDossierPat' => 'float',
		'TotReglPat' => 'float',
		'remise' => 'float'
	];

	protected $fillable = [
		'Numfacture',
		'DtFacture',
		'NomPatient',
		'Assureur',
		'Actes',
		'Dents',
		'TelephonePatient',
		'Qte',
		'PU',
		'Soustotal',
		'TotFacture',
		'TotalPEC',
		'TotalFactPatient',
		'IntituleTotal',
		'EnLettre',
		'DtNaissance',
		'Genre',
		'NumDossierPat',
		'Type',
		'Medecin',
		'TelCabinet',
		'TotReglPat',
		'ActesAr',
		'TypeAr',
		'NomAr',
		'remise'
	];
}
