<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CurrentuserEnregistrement
 * 
 * @property float $iduser
 * @property string|null $typeoperation
 *
 * @package App\Models
 */
class CurrentuserEnregistrement extends Model
{
	protected $table = 'currentuser_enregistrement';
	protected $primaryKey = 'iduser';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'iduser' => 'float'
	];

	protected $fillable = [
		'typeoperation'
	];
}
