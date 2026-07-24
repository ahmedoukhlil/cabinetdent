<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Medicament
 * 
 * @property int $IDMedic
 * @property string $LibelleMedic
 * @property int $fkidtype
 * @property float $PrixRef
 *
 * @package App\Models
 */
class Medicament extends Model
{
	protected $table = 'medicaments';
	protected $primaryKey = 'IDMedic';
	public $timestamps = false;

	protected $casts = [
		'fkidtype'   => 'int',
		'PrixRef'    => 'float',
		'estInterne' => 'boolean',
	];

	protected $fillable = [
		'LibelleMedic',
		'fkidtype',
		'PrixRef',
		'estInterne',
	];

	/**
	 * Scope pour les médicaments
	 */
	public function scopeMedicaments($query)
	{
		return $query->where('fkidtype', 1);
	}

	/**
	 * Scope pour les analyses
	 */
	public function scopeAnalyses($query)
	{
		return $query->where('fkidtype', 2);
	}

	/**
	 * Scope pour les radios
	 */
	public function scopeRadios($query)
	{
		return $query->where('fkidtype', 3);
	}

	/**
	 * Obtenir le type en texte
	 */
	public function getTypeTextAttribute()
	{
		return match($this->fkidtype) {
			1 => 'Médicament',
			2 => 'Analyse',
			3 => 'Radio',
			default => 'Inconnu'
		};
	}
}
