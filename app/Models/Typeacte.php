<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Typeacte
 * 
 * @property int $id
 * @property string $Type
 * @property string $CodeType
 * @property int $ISvisible
 * @property int $NumeroOrdre
 *
 * @package App\Models
 */
class Typeacte extends Model
{
	protected $table = 'typeactes';
	public $timestamps = false;

	protected $casts = [
		'ISvisible' => 'int',
		'NumeroOrdre' => 'int'
	];

	protected $fillable = [
		'Type',
		'CodeType',
		'ISvisible',
		'NumeroOrdre'
	];
}
