<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reglement
 * 
 * @property int $idreglement
 * @property int|null $fkidFactBord
 * @property int|null $fkidTiers
 * @property Carbon|null $dtreglement
 * @property string|null $typeReglement
 * @property float $MontantDep
 * @property int $fkiduser
 * @property string|null $Motif
 * @property int $fkidtypeDepRectte
 * @property int $FkidtypeTiers
 * @property float $MontantRec
 *
 * @package App\Models
 */
class Reglement extends Model
{
	protected $table = 'reglements';
	protected $primaryKey = 'idreglement';
	public $timestamps = false;

	protected $casts = [
		'fkidFactBord' => 'int',
		'fkidTiers' => 'int',
		'dtreglement' => 'datetime',
		'MontantDep' => 'float',
		'fkiduser' => 'int',
		'fkidtypeDepRectte' => 'int',
		'FkidtypeTiers' => 'int',
		'MontantRec' => 'float'
	];

	protected $fillable = [
		'fkidFactBord',
		'fkidTiers',
		'dtreglement',
		'typeReglement',
		'MontantDep',
		'fkiduser',
		'Motif',
		'fkidtypeDepRectte',
		'FkidtypeTiers',
		'MontantRec'
	];

	public function facture()
	{
		return $this->belongsTo(Facture::class, 'fkidFactBord', 'Idfacture');
	}
}
