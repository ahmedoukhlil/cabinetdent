<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Detailbc
 * 
 * @property int $idDetailBC
 * @property int $FkidBC
 * @property string|null $Designation
 * @property float $Qte
 * @property Carbon|null $DtCr
 * @property float $MontantTotal
 * @property float $PU
 *
 * @package App\Models
 */
class Detailbc extends Model
{
	protected $table = 'detailbc';
	protected $primaryKey = 'idDetailBC';
	public $timestamps = false;

	protected $casts = [
		'FkidBC' => 'int',
		'Qte' => 'float',
		'DtCr' => 'datetime',
		'MontantTotal' => 'float',
		'PU' => 'float'
	];

	protected $fillable = [
		'FkidBC',
		'Designation',
		'Qte',
		'DtCr',
		'MontantTotal',
		'PU'
	];
}
