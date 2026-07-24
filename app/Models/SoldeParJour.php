<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SoldeParJour
 * 
 * @property int|null $idoperation
 * @property float|null $solde_debit
 * @property float|null $Solde_Credit
 *
 * @package App\Models
 */
class SoldeParJour extends Model
{
	protected $table = 'solde_par_jour';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idoperation' => 'int',
		'solde_debit' => 'float',
		'Solde_Credit' => 'float'
	];

	protected $fillable = [
		'idoperation',
		'solde_debit',
		'Solde_Credit'
	];
}
