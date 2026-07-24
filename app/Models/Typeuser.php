<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TypeUser
 * 
 * @property int $ID
 * @property string $Libelle
 * @property string $Statut
 *
 * @package App\Models
 */
class TypeUser extends Model
{
	protected $table = 'typeuser';
	protected $primaryKey = 'IdClasseUser0';
	public $timestamps = false;

	protected $fillable = [
		'Libelle'
	];

	// Relation avec les utilisateurs
	public function users()
	{
		return $this->hasMany(User::class, 'IdClasseUser', 'IdClasseUser0');
	}
}
