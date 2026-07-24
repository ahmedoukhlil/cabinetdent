<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fichetraitement
 * 
 * @property int $idFicheTraitement
 * @property int $fkidfacture
 * @property int $fkidacte
 * @property int $fkidmedecin
 * @property string $Acte
 * @property string $Traitement
 * @property float $Prix
 * @property Carbon|null $dateTraite
 * @property string $NomMedecin
 * @property int $Ordre
 * @property int $IsImprimer
 * @property int $IsSupprimer
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class Fichetraitement extends Model
{
	protected $table = 'fichetraitement';
	protected $primaryKey = 'idFicheTraitement';
	public $timestamps = false;

	protected $casts = [
		'fkidfacture' => 'int',
		'fkidacte' => 'int',
		'fkidmedecin' => 'int',
		'Prix' => 'float',
		'dateTraite' => 'datetime',
		'Ordre' => 'int',
		'IsImprimer' => 'int',
		'IsSupprimer' => 'int',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'fkidfacture',
		'fkidacte',
		'fkidmedecin',
		'Acte',
		'Traitement',
		'Prix',
		'dateTraite',
		'NomMedecin',
		'Ordre',
		'IsImprimer',
		'IsSupprimer',
		'fkidCabinet'
	];

	// Relations
	public function facture()
	{
		return $this->belongsTo(\App\Models\Facture::class, 'fkidfacture', 'Idfacture');
	}

	public function acte()
	{
		return $this->belongsTo(\App\Models\Acte::class, 'fkidacte', 'ID');
	}

	public function medecin()
	{
		return $this->belongsTo(\App\Models\Medecin::class, 'fkidmedecin', 'idMedecin');
	}
}
