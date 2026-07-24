<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TBanque
 * 
 * @property int $Id_BNQ
 * @property string|null $LibBanque
 * @property string|null $Ncompte_Bnq
 * @property string|null $CompteDebit
 * @property float|null $Isvisible
 *
 * @package App\Models
 */
class TBanque extends Model
{
	protected $table = 't_banque';
	protected $primaryKey = 'Id_BNQ';
	public $timestamps = false;

	protected $casts = [
		'Isvisible' => 'float'
	];

	protected $fillable = [
		'LibBanque',
		'Ncompte_Bnq',
		'CompteDebit',
		'Isvisible'
	];
}
