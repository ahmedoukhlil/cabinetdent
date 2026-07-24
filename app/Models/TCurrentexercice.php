<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TCurrentexercice
 * 
 * @property float $idexercice
 * @property string|null $archive
 *
 * @package App\Models
 */
class TCurrentexercice extends Model
{
	protected $table = 't_currentexercice';
	protected $primaryKey = 'idexercice';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idexercice' => 'float'
	];

	protected $fillable = [
		'archive'
	];
}
