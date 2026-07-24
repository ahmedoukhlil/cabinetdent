<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ficheaimprimerautrefoi
 * 
 * @property int $idFicheAImprimer
 * @property string|null $Traitement
 * @property Carbon|null $DateTraitement
 * @property float|null $Prix
 * @property int $NumLigneAImprimer
 * @property int $fkidfacture
 *
 * @package App\Models
 */
class Ficheaimprimerautrefoi extends Model
{
	protected $table = 'ficheaimprimerautrefois';
	protected $primaryKey = 'idFicheAImprimer';
	public $timestamps = false;

	protected $casts = [
		'DateTraitement' => 'datetime',
		'Prix' => 'float',
		'NumLigneAImprimer' => 'int',
		'fkidfacture' => 'int'
	];

	protected $fillable = [
		'Traitement',
		'DateTraitement',
		'Prix',
		'NumLigneAImprimer',
		'fkidfacture'
	];
}
