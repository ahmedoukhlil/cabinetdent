<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Typereglement
 * 
 * @property int $idreglement
 * @property string|null $TypeReglement
 *
 * @package App\Models
 */
class Typereglement extends Model
{
	protected $table = 'typereglements';
	protected $primaryKey = 'idreglement';
	public $timestamps = false;

	protected $fillable = [
		'TypeReglement'
	];
}
