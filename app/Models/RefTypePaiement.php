<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RefTypePaiement
 * 
 * @property int $idtypepaie
 * @property string $LibPaie
 *
 * @package App\Models
 */
class RefTypePaiement extends Model
{
	protected $table = 'ref_type_paiement';
	protected $primaryKey = 'idtypepaie';
	public $timestamps = false;

	protected $fillable = [
		'LibPaie'
	];
}
