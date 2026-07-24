<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Acte
 * 
 * @property int $ID
 * @property string|null $Acte
 * @property float $PrixRef
 * @property int $fkidTypeActe
 * @property int $nordre
 * @property string $user
 * @property int $fkidassureur
 * @property string $ActeArab
 * @property int $Masquer
 *
 * @package App\Models
 */
class Acte extends Model
{
	protected $table = 'actes';
	protected $primaryKey = 'ID';
	public $timestamps = false;

	protected $casts = [
		'PrixRef' => 'float',
		'fkidTypeActe' => 'int',
		'nordre' => 'int',
		'fkidassureur' => 'int',
		'Masquer' => 'int'
	];

	protected $fillable = [
		'Acte',
		'PrixRef',
		'fkidTypeActe',
		'nordre',
		'user',
		'fkidassureur',
		'ActeArab',
		'Masquer'
	];

	// Accesseurs pour la compatibilité avec le composant Livewire
	public function getActeNomAttribute()
	{
		return $this->Acte;
	}

	public function setActeNomAttribute($value)
	{
		$this->Acte = $value;
	}

	public function getMontantAttribute()
	{
		return $this->PrixRef;
	}

	public function setMontantAttribute($value)
	{
		$this->PrixRef = $value;
	}

	public function assureur()
	{
		return $this->belongsTo(Assureur::class, 'fkidassureur', 'IDAssureur');
	}

	public function typeActe()
	{
		return $this->belongsTo(Typeacte::class, 'fkidTypeActe', 'id');
	}
}
