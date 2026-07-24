<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fichierscanne
 * 
 * @property int $idfichier
 * @property string|null $NomFichier
 * @property string|null $Chemindaccess
 * @property string|null $Numfacture
 * @property float|null $Fkidfacture
 * @property int|null $fkidpatient
 * @property Carbon|null $dtajoutPJ
 * @property string|null $user
 * @property string|null $CategoriePJ
 *
 * @package App\Models
 */
class Fichierscanne extends Model
{
	protected $table = 'fichierscanne';
	protected $primaryKey = 'idfichier';
	public $timestamps = false;

	protected $casts = [
		'Fkidfacture' => 'float',
		'fkidpatient' => 'int',
		'dtajoutPJ' => 'datetime'
	];

	protected $fillable = [
		'NomFichier',
		'Chemindaccess',
		'Numfacture',
		'Fkidfacture',
		'fkidpatient',
		'dtajoutPJ',
		'user',
		'CategoriePJ'
	];
}
