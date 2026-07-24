<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Typerecettesdepense
 * 
 * @property int $IdtypeDepRec
 * @property string $LibelleType
 *
 * @package App\Models
 */
class Typerecettesdepense extends Model
{
	protected $table = 'typerecettesdepenses';
	protected $primaryKey = 'IdtypeDepRec';
	public $timestamps = false;

	protected $fillable = [
		'LibelleType'
	];
}
