<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bordereauxfacture
 * 
 * @property int $IDBordFacture
 * @property string|null $NumBord
 * @property int|null $nordre
 * @property int|null $anneeBord
 * @property string|null $PeriodeFacture
 * @property Carbon|null $DtCreation
 * @property Carbon|null $DtGeneration
 * @property float|null $MontantFacture
 * @property float|null $MontantPatient
 * @property float|null $MontantPEC
 * @property float|null $MontantPayeAssureur
 * @property string|null $user
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class Bordereauxfacture extends Model
{
	protected $table = 'bordereauxfactures';
	protected $primaryKey = 'IDBordFacture';
	public $timestamps = false;

	protected $casts = [
		'nordre' => 'int',
		'anneeBord' => 'int',
		'DtCreation' => 'datetime',
		'DtGeneration' => 'datetime',
		'MontantFacture' => 'float',
		'MontantPatient' => 'float',
		'MontantPEC' => 'float',
		'MontantPayeAssureur' => 'float',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'NumBord',
		'nordre',
		'anneeBord',
		'PeriodeFacture',
		'DtCreation',
		'DtGeneration',
		'MontantFacture',
		'MontantPatient',
		'MontantPEC',
		'MontantPayeAssureur',
		'user',
		'fkidCabinet'
	];
}
