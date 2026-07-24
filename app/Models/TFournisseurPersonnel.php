<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TFournisseurPersonnel
 * 
 * @property int $IDFournisseur
 * @property string|null $NomTiers
 * @property string|null $TelephoneAutre
 * @property float|null $fkidtypeTiers
 * @property float|null $userCr
 * @property int $fkidcaibnet
 *
 * @package App\Models
 */
class TFournisseurPersonnel extends Model
{
	protected $table = 't_fournisseur_personnel';
	protected $primaryKey = 'IDFournisseur';
	public $timestamps = false;

	protected $casts = [
		'fkidtypeTiers' => 'float',
		'userCr' => 'float',
		'fkidcaibnet' => 'int'
	];

	protected $fillable = [
		'NomTiers',
		'TelephoneAutre',
		'fkidtypeTiers',
		'userCr',
		'fkidcaibnet'
	];
}
