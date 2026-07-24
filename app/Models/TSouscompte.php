<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TSouscompte
 * 
 * @property int $id
 * @property float|null $Ncompte
 * @property float|null $Numordresouscompte
 * @property string|null $souscompte
 * @property Carbon|null $dtcreation
 * @property float|null $iduser_code
 *
 * @package App\Models
 */
class TSouscompte extends Model
{
	protected $table = 't_souscompte';
	public $timestamps = false;

	protected $casts = [
		'Ncompte' => 'float',
		'Numordresouscompte' => 'float',
		'dtcreation' => 'datetime',
		'iduser_code' => 'float'
	];

	protected $fillable = [
		'Ncompte',
		'Numordresouscompte',
		'souscompte',
		'dtcreation',
		'iduser_code'
	];
}
