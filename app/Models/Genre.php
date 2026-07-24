<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Genre
 * 
 * @property string|null $Genre
 * @property int $idGenre
 *
 * @package App\Models
 */
class Genre extends Model
{
	protected $table = 'genre';
	protected $primaryKey = 'idGenre';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idGenre' => 'int'
	];

	protected $fillable = [
		'Genre'
	];
}
