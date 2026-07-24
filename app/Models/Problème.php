<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Problème
 * 
 * @property int|null $Champ1
 * @property int|null $ID
 * @property string|null $Résumé
 * @property string|null $État
 * @property string|null $Priorité
 * @property string|null $Catégorie
 * @property string|null $Projet
 * @property Carbon|null $DateOuverture
 * @property Carbon|null $Échéance
 * @property string|null $MotsClés
 * @property string|null $Résolution
 * @property string|null $VersionRésolue
 * @property string|null $PiècesJointes
 *
 * @package App\Models
 */
class Problème extends Model
{
	protected $table = 'problèmes';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Champ1' => 'int',
		'ID' => 'int',
		'DateOuverture' => 'datetime',
		'Échéance' => 'datetime'
	];

	protected $fillable = [
		'Champ1',
		'ID',
		'Résumé',
		'État',
		'Priorité',
		'Catégorie',
		'Projet',
		'DateOuverture',
		'Échéance',
		'MotsClés',
		'Résolution',
		'VersionRésolue',
		'PiècesJointes'
	];
}
