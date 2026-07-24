<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TSoldeCaisse
 * 
 * @property int $idoper
 * @property string|null $typeoperation
 * @property float|null $idtypeoperation
 * @property float|null $xclie
 * @property float|null $especesortie
 * @property float|null $especeentre
 * @property Carbon|null $dateoper
 * @property Carbon|null $dtgeneral
 * @property float|null $iduser
 * @property float|null $exercice
 * @property float|null $xboutique
 *
 * @package App\Models
 */
class TSoldeCaisse extends Model
{
	protected $table = 't_solde_caisse';
	protected $primaryKey = 'idoper';
	public $timestamps = false;

	protected $casts = [
		'idtypeoperation' => 'float',
		'xclie' => 'float',
		'especesortie' => 'float',
		'especeentre' => 'float',
		'dateoper' => 'datetime',
		'dtgeneral' => 'datetime',
		'iduser' => 'float',
		'exercice' => 'float',
		'xboutique' => 'float'
	];

	protected $fillable = [
		'typeoperation',
		'idtypeoperation',
		'xclie',
		'especesortie',
		'especeentre',
		'dateoper',
		'dtgeneral',
		'iduser',
		'exercice',
		'xboutique'
	];
}
