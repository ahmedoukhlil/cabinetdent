<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Typetier
 * 
 * @property int $IdTypeTiers
 * @property string $LibelleTypeTiers
 * @property int $Estvisible
 *
 * @package App\Models
 */
class Typetier extends Model
{
	protected $table = 'typetiers';
	protected $primaryKey = 'IdTypeTiers';
	public $timestamps = false;

	protected $casts = [
		'Estvisible' => 'int'
	];

	protected $fillable = [
		'LibelleTypeTiers',
		'Estvisible'
	];
}
