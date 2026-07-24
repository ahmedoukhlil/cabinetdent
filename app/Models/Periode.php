<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Periode
 * 
 * @property float $idperiode
 * @property string|null $catperiode
 *
 * @package App\Models
 */
class Periode extends Model
{
	protected $table = 'periodes';
	protected $primaryKey = 'idperiode';
	public $timestamps = false;

	protected $fillable = [
		'catperiode'
	];
}
