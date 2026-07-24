<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Recuaimprimer
 * 
 * @property int $IdRecu
 * @property Carbon|null $DtOperation
 * @property string|null $TypeOperation
 * @property float $MontantOperation
 * @property string|null $Beneficiaire
 * @property string|null $TypeBeneficiaire
 * @property string|null $MontantEnLettre
 * @property int $fkidUser
 * @property string|null $Motif
 * @property string|null $Medecin
 * @property string|null $Utilisateur
 * @property int $fkidCabinet
 * @property int $fkidtypePaie
 * @property string $TypePAie
 *
 * @package App\Models
 */
class Recuaimprimer extends Model
{
	protected $table = 'recuaimprimer';
	protected $primaryKey = 'IdRecu';
	public $timestamps = false;

	protected $casts = [
		'DtOperation' => 'datetime',
		'MontantOperation' => 'float',
		'fkidUser' => 'int',
		'fkidCabinet' => 'int',
		'fkidtypePaie' => 'int'
	];

	protected $fillable = [
		'DtOperation',
		'TypeOperation',
		'MontantOperation',
		'Beneficiaire',
		'TypeBeneficiaire',
		'MontantEnLettre',
		'fkidUser',
		'Motif',
		'Medecin',
		'Utilisateur',
		'fkidCabinet',
		'fkidtypePaie',
		'TypePAie'
	];
}
