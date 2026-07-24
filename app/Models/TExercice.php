<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TExercice
 * 
 * @property float $Exercice
 * @property string|null $archiver
 *
 * @package App\Models
 */
class TExercice extends Model
{
	protected $table = 't_exercice';
	protected $primaryKey = 'Exercice';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Exercice' => 'float'
	];

	protected $fillable = [
		'archiver'
	];
}
