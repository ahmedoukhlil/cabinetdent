<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CaisseOperationsimp
 * 
 * @property int $cle
 * @property Carbon|null $dateoper
 * @property float|null $MontantOperation
 * @property string|null $designation
 * @property string|null $Tiers
 * @property float $entreEspece
 * @property float $retraitEspece
 * @property float $pourPatFournisseur
 * @property float $pourCabinet
 * @property int $fkiduser
 * @property float|null $exercice
 * @property string|null $TypeTiers
 * @property float $fkidfacturebord
 * @property Carbon|null $DtCr
 * @property int $fkidCabinet
 * @property int $fkidtypePaie
 * @property string $TypePAie
 *
 * @package App\Models
 */
class CaisseOperationsimp extends Model
{
	protected $table = 'caisse_operationsimp';
	protected $primaryKey = 'cle';
	public $timestamps = false;

	protected $casts = [
		'dateoper' => 'datetime',
		'MontantOperation' => 'float',
		'entreEspece' => 'float',
		'retraitEspece' => 'float',
		'pourPatFournisseur' => 'float',
		'pourCabinet' => 'float',
		'fkiduser' => 'int',
		'exercice' => 'float',
		'fkidfacturebord' => 'float',
		'DtCr' => 'datetime',
		'fkidCabinet' => 'int',
		'fkidtypePaie' => 'int'
	];

	protected $fillable = [
		'dateoper',
		'MontantOperation',
		'designation',
		'Tiers',
		'entreEspece',
		'retraitEspece',
		'pourPatFournisseur',
		'pourCabinet',
		'fkiduser',
		'exercice',
		'TypeTiers',
		'fkidfacturebord',
		'DtCr',
		'fkidCabinet',
		'fkidtypePaie',
		'TypePAie'
	];
}
