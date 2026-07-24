<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TTypeOperation
 * 
 * @property int $id
 * @property string|null $liboperation
 * @property float|null $isvisibleemployer
 * @property float|null $isvisibleclient
 * @property float|null $iscaisse
 * @property string|null $lib
 * @property string|null $cat_operation
 * @property float|null $isvisiblerech
 * @property string|null $lib2
 * @property float|null $categorie
 *
 * @package App\Models
 */
class TTypeOperation extends Model
{
	protected $table = 't_type_operation';
	public $timestamps = false;

	protected $casts = [
		'isvisibleemployer' => 'float',
		'isvisibleclient' => 'float',
		'iscaisse' => 'float',
		'isvisiblerech' => 'float',
		'categorie' => 'float'
	];

	protected $fillable = [
		'liboperation',
		'isvisibleemployer',
		'isvisibleclient',
		'iscaisse',
		'lib',
		'cat_operation',
		'isvisiblerech',
		'lib2',
		'categorie'
	];
}
