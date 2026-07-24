<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TArretSituationCompte
 * 
 * @property int|null $idarret
 * @property Carbon|null $dateoper
 * @property float|null $Ncompte
 * @property float|null $MontantOperation
 * @property float|null $typeoperation
 * @property string|null $designation
 * @property string|null $Numfacture
 * @property float|null $fkidboutique
 * @property float|null $entreEspece
 * @property float|null $retraitEspece
 * @property float|null $pourlui
 * @property float|null $pourbtique
 * @property string|null $nombene
 * @property string|null $telebene
 * @property float|null $compmouv
 * @property float|null $remi
 * @property string|null $numecheqbanq
 * @property int|null $User_code
 * @property int|null $typedepense
 * @property float|null $exercice
 * @property string|null $archive
 * @property string|null $soldetouche
 * @property Carbon|null $dtgeneraloper
 * @property string|null $operationsurfacture
 *
 * @package App\Models
 */
class TArretSituationCompte extends Model
{
	protected $table = 't_arret_situation_compte';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idarret' => 'int',
		'dateoper' => 'datetime',
		'Ncompte' => 'float',
		'MontantOperation' => 'float',
		'typeoperation' => 'float',
		'fkidboutique' => 'float',
		'entreEspece' => 'float',
		'retraitEspece' => 'float',
		'pourlui' => 'float',
		'pourbtique' => 'float',
		'compmouv' => 'float',
		'remi' => 'float',
		'User_code' => 'int',
		'typedepense' => 'int',
		'exercice' => 'float',
		'dtgeneraloper' => 'datetime'
	];

	protected $fillable = [
		'idarret',
		'dateoper',
		'Ncompte',
		'MontantOperation',
		'typeoperation',
		'designation',
		'Numfacture',
		'fkidboutique',
		'entreEspece',
		'retraitEspece',
		'pourlui',
		'pourbtique',
		'nombene',
		'telebene',
		'compmouv',
		'remi',
		'numecheqbanq',
		'User_code',
		'typedepense',
		'exercice',
		'archive',
		'soldetouche',
		'dtgeneraloper',
		'operationsurfacture'
	];
}
