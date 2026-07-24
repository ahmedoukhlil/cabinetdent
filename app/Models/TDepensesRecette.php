<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TDepensesRecette
 * 
 * @property int $iddepenserecette
 * @property Carbon|null $dtdepense
 * @property Carbon|null $dtajoutdepense
 * @property float|null $idexercice
 * @property string|null $designation
 * @property string|null $modereglement
 * @property string|null $numtitreregl
 * @property string|null $typedep
 * @property float|null $xbeneficiaire
 * @property float|null $mntentree
 * @property float|null $mntsortie
 * @property string|null $nomuser
 * @property string|null $user
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class TDepensesRecette extends Model
{
	protected $table = 't_depenses_recette';
	protected $primaryKey = 'iddepenserecette';
	public $timestamps = false;

	protected $casts = [
		'dtdepense' => 'datetime',
		'dtajoutdepense' => 'datetime',
		'idexercice' => 'float',
		'xbeneficiaire' => 'float',
		'mntentree' => 'float',
		'mntsortie' => 'float',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'dtdepense',
		'dtajoutdepense',
		'idexercice',
		'designation',
		'modereglement',
		'numtitreregl',
		'typedep',
		'xbeneficiaire',
		'mntentree',
		'mntsortie',
		'nomuser',
		'user',
		'fkidCabinet'
	];
}
