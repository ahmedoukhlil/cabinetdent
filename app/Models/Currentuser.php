<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Currentuser
 * 
 * @property int $User_code
 * @property string|null $User_Nom_Connect
 * @property string|null $User_Pwd
 * @property string|null $User_fonction
 * @property string|null $User_NomPren
 * @property float|null $visibleuser
 *
 * @package App\Models
 */
class Currentuser extends Model
{
	protected $table = 'currentuser';
	protected $primaryKey = 'User_code';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'User_code' => 'int',
		'visibleuser' => 'float'
	];

	protected $fillable = [
		'User_Nom_Connect',
		'User_Pwd',
		'User_fonction',
		'User_NomPren',
		'visibleuser'
	];
}
