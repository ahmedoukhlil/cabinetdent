<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 * 
 * @property float $idCategorie
 * @property string|null $LiblCat
 * @property float|null $etat
 * @property int|null $Code_user
 * @property int|null $type
 *
 * @package App\Models
 */
class Category extends Model
{
	protected $table = 'categories';
	protected $primaryKey = 'idCategorie';
	public $timestamps = false;

	protected $casts = [
		'etat' => 'float',
		'Code_user' => 'int',
		'type' => 'int'
	];

	protected $fillable = [
		'LiblCat',
		'etat',
		'Code_user',
		'type'
	];
}
