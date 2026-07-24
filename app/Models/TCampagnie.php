<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TCampagnie
 * 
 * @property string|null $IDCampagnie
 * @property string|null $NomCampagnie
 * @property string|null $Code
 *
 * @package App\Models
 */
class TCampagnie extends Model
{
	protected $table = 't_campagnie';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'IDCampagnie',
		'NomCampagnie',
		'Code'
	];
}
