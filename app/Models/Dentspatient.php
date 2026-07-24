<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Dentspatient
 * 
 * @property int $idDentsPatients
 * @property int $fkidfacture
 * @property string $NumDent
 * @property Carbon $DtAjout
 * @property int $fkidpatient
 * @property int $FkidActe
 * @property string $acte
 * @property int $fkidmedecin
 *
 * @package App\Models
 */
class Dentspatient extends Model
{
	protected $table = 'dentspatients';
	protected $primaryKey = 'idDentsPatients';
	public $timestamps = false;

	protected $casts = [
		'fkidfacture' => 'int',
		'DtAjout' => 'datetime',
		'fkidpatient' => 'int',
		'FkidActe' => 'int',
		'fkidmedecin' => 'int'
	];

	protected $fillable = [
		'fkidfacture',
		'NumDent',
		'DtAjout',
		'fkidpatient',
		'FkidActe',
		'acte',
		'fkidmedecin'
	];
}
