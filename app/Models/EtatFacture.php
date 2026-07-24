<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EtatFacture
 * 
 * @property int $idetatfacture
 * @property float|null $idetatfact
 * @property string|null $libelle
 * @property string|null $niveau
 * @property float|null $iscaisse
 * @property float|null $isvisibledevis
 *
 * @package App\Models
 */
class EtatFacture extends Model
{
	protected $table = 'etat_facture';
	protected $primaryKey = 'idetatfacture';
	public $timestamps = false;

	protected $casts = [
		'idetatfact' => 'float',
		'iscaisse' => 'float',
		'isvisibledevis' => 'float'
	];

	protected $fillable = [
		'idetatfact',
		'libelle',
		'niveau',
		'iscaisse',
		'isvisibledevis'
	];
}
