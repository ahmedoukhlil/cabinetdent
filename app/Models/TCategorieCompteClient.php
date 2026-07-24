<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TCategorieCompteClient
 * 
 * @property int $Codecategorie
 * @property string|null $libecate
 * @property int|null $cle
 * @property float|null $clas
 * @property float|null $numecompcate
 * @property string|null $libecatearab
 * @property float|null $derniernum
 * @property float|null $isvisible
 * @property float|null $isvisiblefact
 * @property float|null $isvisibleoper
 * @property float|null $numTris
 *
 * @package App\Models
 */
class TCategorieCompteClient extends Model
{
	protected $table = 't_categorie_compte_client';
	protected $primaryKey = 'Codecategorie';
	public $timestamps = false;

	protected $casts = [
		'cle' => 'int',
		'clas' => 'float',
		'numecompcate' => 'float',
		'derniernum' => 'float',
		'isvisible' => 'float',
		'isvisiblefact' => 'float',
		'isvisibleoper' => 'float',
		'numTris' => 'float'
	];

	protected $fillable = [
		'libecate',
		'cle',
		'clas',
		'numecompcate',
		'libecatearab',
		'derniernum',
		'isvisible',
		'isvisiblefact',
		'isvisibleoper',
		'numTris'
	];
}
