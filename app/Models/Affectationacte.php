<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Affectationacte
 * 
 * @property int $idAffectationActes
 * @property int $fkidfacture
 * @property int $fkidpatient
 * @property Carbon|null $DtActe
 * @property float $MontantActe
 * @property int $fkidMedecin
 * @property string|null $DescriptionActe
 *
 * @package App\Models
 */
class Affectationacte extends Model
{
	protected $table = 'affectationactes';
	protected $primaryKey = 'idAffectationActes';
	public $timestamps = false;

	protected $casts = [
		'fkidfacture' => 'int',
		'fkidpatient' => 'int',
		'DtActe' => 'datetime',
		'MontantActe' => 'float',
		'fkidMedecin' => 'int'
	];

	protected $fillable = [
		'fkidfacture',
		'fkidpatient',
		'DtActe',
		'MontantActe',
		'fkidMedecin',
		'DescriptionActe'
	];
}
