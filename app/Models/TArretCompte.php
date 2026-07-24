<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TArretCompte
 * 
 * @property int|null $iderret
 * @property float|null $ncompte
 * @property float|null $soldedebit
 * @property float|null $soldecredit
 * @property float|null $idboutique
 * @property float|null $exercice
 * @property float|null $fkidcleoperation
 * @property float|null $User_code
 * @property Carbon|null $dtgeneral
 *
 * @package App\Models
 */
class TArretCompte extends Model
{
	protected $table = 't_arret_compte';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'iderret' => 'int',
		'ncompte' => 'float',
		'soldedebit' => 'float',
		'soldecredit' => 'float',
		'idboutique' => 'float',
		'exercice' => 'float',
		'fkidcleoperation' => 'float',
		'User_code' => 'float',
		'dtgeneral' => 'datetime'
	];

	protected $fillable = [
		'iderret',
		'ncompte',
		'soldedebit',
		'soldecredit',
		'idboutique',
		'exercice',
		'fkidcleoperation',
		'User_code',
		'dtgeneral'
	];
}
