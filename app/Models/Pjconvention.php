<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pjconvention
 * 
 * @property int $IdPJ
 * @property int|null $fkidassureur
 * @property string|null $NomFichier
 * @property string|null $Chemindaccess
 * @property Carbon|null $dtajoutPJ
 * @property string|null $user
 *
 * @package App\Models
 */
class Pjconvention extends Model
{
	protected $table = 'pjconvention';
	protected $primaryKey = 'IdPJ';
	public $timestamps = false;

	protected $casts = [
		'fkidassureur' => 'int',
		'dtajoutPJ' => 'datetime'
	];

	protected $fillable = [
		'fkidassureur',
		'NomFichier',
		'Chemindaccess',
		'dtajoutPJ',
		'user'
	];
}
