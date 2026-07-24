<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Boncommande
 * 
 * @property int $idBC
 * @property string $NumBC
 * @property Carbon|null $DtCreation
 * @property int $num
 * @property float $Annee
 * @property int $fkidfournisseur
 * @property string|null $fournisseur
 * @property string $TypeDemande
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class Boncommande extends Model
{
	protected $table = 'boncommande';
	protected $primaryKey = 'idBC';
	public $timestamps = false;

	protected $casts = [
		'DtCreation' => 'datetime',
		'num' => 'int',
		'Annee' => 'float',
		'fkidfournisseur' => 'int',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'NumBC',
		'DtCreation',
		'num',
		'Annee',
		'fkidfournisseur',
		'fournisseur',
		'TypeDemande',
		'fkidCabinet'
	];
}
