<?php

/**
 * Created by Reliese Model.
 */
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class CaisseOperation
 * 
 * @property int $cle
 * @property Carbon|null $dateoper
 * @property float|null $MontantOperation
 * @property string|null $designation
 * @property float|null $fkidTiers
 * @property float $entreEspece
 * @property float $retraitEspece
 * @property float $pourPatFournisseur
 * @property float $pourCabinet
 * @property int $fkiduser
 * @property float|null $exercice
 * @property int $fkIdTypeTiers
 * @property float $fkidfacturebord
 * @property Carbon|null $DtCr
 * @property int $fkidcabinet
 * @property int $fkidtypePaie
 * @property string $TypePAie
 * @property int $fkidmedecin
 * @property string $medecin
 *
 * @package App\Models
 */
class CaisseOperation extends Model
{
	protected $table = 'caisse_operations';
	protected $primaryKey = 'cle';
	public $timestamps = false;

	protected $casts = [
		'dateoper' => 'datetime',
		'MontantOperation' => 'float',
		'fkidTiers' => 'integer',
		'entreEspece' => 'float',
		'retraitEspece' => 'float',
		'pourPatFournisseur' => 'float',
		'pourCabinet' => 'float',
		'fkiduser' => 'integer',
		'exercice' => 'integer',
		'fkIdTypeTiers' => 'integer',
		'fkidfacturebord' => 'integer',
		'DtCr' => 'datetime',
		'fkidcabinet' => 'int',
		'fkidtypePaie' => 'int',
		'fkidmedecin' => 'int'
	];

	protected $fillable = [
		'dateoper',
		'MontantOperation',
		'designation',
		'fkidTiers',
		'entreEspece',
		'retraitEspece',
		'pourPatFournisseur',
		'pourCabinet',
		'fkiduser',
		'exercice',
		'fkIdTypeTiers',
		'fkidfacturebord',
		'DtCr',
		'fkidcabinet',
		'fkidtypePaie',
		'TypePAie',
		'fkidmedecin',
		'medecin'
	];

	// Relations
	public function medecin()
	{
		return $this->belongsTo(Medecin::class, 'fkidmedecin', 'idMedecin');
	}

	public function user()
	{
		return $this->belongsTo(TUser::class, 'fkiduser', 'Iduser');
	}

	public function cabinet()
	{
		return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idCabinet');
	}

	public function tiers()
	{
		return $this->belongsTo(Patient::class, 'fkidTiers', 'ID');
	}
}
