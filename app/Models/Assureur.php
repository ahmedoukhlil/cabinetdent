<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Assureur
 * 
 * @property int $IDAssureur
 * @property string|null $LibAssurance
 * @property float|null $TauxdePEC
 * @property float $SeuilDevis
 * @property int $ISTP
 * @property Carbon|null $DtConvention
 * @property string|null $ContactAssureur
 * @property string|null $AdresseMail
 * @property string|null $user
 * @property string|null $Adresse
 * @property int $fkidtypeTiers
 *
 * @package App\Models
 */
class Assureur extends Model
{
	protected $table = 'assureurs';
	protected $primaryKey = 'IDAssureur';
	public $incrementing = true;
	public $keyType = 'int';
	public $timestamps = false;

	protected $casts = [
		'TauxdePEC' => 'float',
		'SeuilDevis' => 'float',
		'ISTP' => 'int',
		'DtConvention' => 'datetime',
		'fkidtypeTiers' => 'int'
	];

	protected $fillable = [
		'LibAssurance',
		'TauxdePEC',
		'SeuilDevis',
		'ISTP',
		'DtConvention',
		'ContactAssureur',
		'AdresseMail',
		'user',
		'Adresse',
		'fkidtypeTiers'
	];
}
