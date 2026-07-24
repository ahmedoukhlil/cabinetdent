<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ordonnance
 * 
 * @property int $IDOrdonnances
 * @property string|null $Libelle
 * @property Carbon|null $DtPrescription
 * @property int|null $fkidrefOrd
 * @property int|null $NumordreOrd
 * @property string|null $Utilisation
 * @property int $fkiduser
 *
 * @package App\Models
 */
class Ordonnance extends Model
{
	protected $table = 'ordonnances';
	protected $primaryKey = 'IDOrdonnances';
	public $timestamps = false;

	protected $casts = [
		'DtPrescription' => 'datetime',
		'fkidrefOrd' => 'int',
		'NumordreOrd' => 'int',
		'fkiduser' => 'int',
		'estInterne' => 'bool',
	];

	protected $fillable = [
		'Libelle',
		'DtPrescription',
		'fkidrefOrd',
		'NumordreOrd',
		'Utilisation',
		'Quantite',
		'fkiduser',
		'estInterne',
	];

	/**
	 * Relation avec l'ordonnance référence
	 */
	public function ordonnanceRef()
	{
		return $this->belongsTo(Ordonnanceref::class, 'fkidrefOrd', 'id');
	}

	/**
	 * Relation avec l'utilisateur
	 */
	public function user()
	{
		return $this->belongsTo(\App\Models\TUser::class, 'fkiduser', 'Iduser');
	}
}
