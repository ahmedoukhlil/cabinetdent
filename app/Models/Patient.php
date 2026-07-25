<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class Patient
 * 
 * @property int $ID
 * @property string|null $Prenom
 * @property string|null $Nom
 * @property string|null $NNI
 * @property Carbon|null $DtNaissance
 * @property string|null $Genre
 * @property string|null $IdentifiantAssurance
 * @property int $Assureur
 * @property string|null $Telephone1
 * @property string|null $Telephone2
 * @property string|null $MatriculeFonct
 * @property string|null $NomContact
 * @property string|null $ClasserSous
 * @property bool|null $choix
 * @property string|null $user
 * @property float $IdentifiantPatient
 * @property Carbon|null $Dtajout
 * @property int|null $fkidUser
 * @property string|null $Adresse
 * @property Carbon|null $DerniereDtOper
 * @property int $DernDossierFermer
 * @property Carbon|null $DtDernFermeture
 * @property int $fkidtypeTiers
 * @property Carbon|null $DtDernierRDV
 * @property int $fkidcabinet
 *
 * @package App\Models
 */
class Patient extends Authenticatable
{
	use Notifiable;

	protected $table = 'patients';
	protected $primaryKey = 'ID';
	public $timestamps = false;

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected $casts = [
		'DtNaissance' => 'datetime',
		'Assureur' => 'int',
		'choix' => 'bool',
		'IdentifiantPatient' => 'float',
		'Dtajout' => 'datetime',
		'fkidUser' => 'int',
		'DerniereDtOper' => 'datetime',
		'DernDossierFermer' => 'int',
		'DtDernFermeture' => 'datetime',
		'fkidtypeTiers' => 'int',
		'DtDernierRDV' => 'datetime',
		'fkidcabinet' => 'int',
		'mdp_defini_le' => 'datetime'
	];

	protected $fillable = [
		'Prenom',
		'Nom',
		'NNI',
		'DtNaissance',
		'Genre',
		'IdentifiantAssurance',
		'Assureur',
		'Telephone1',
		'Telephone2',
		'MatriculeFonct',
		'NomContact',
		'ClasserSous',
		'choix',
		'user',
		'IdentifiantPatient',
		'Dtajout',
		'fkidUser',
		'Adresse',
		'DerniereDtOper',
		'DernDossierFermer',
		'DtDernFermeture',
		'fkidtypeTiers',
		'DtDernierRDV',
		'fkidcabinet',
		'password',
		'mdp_defini_le'
	];

	/**
	 * Relation avec l'assureur
	 */
	public function assureur()
	{
		return $this->belongsTo(\App\Models\Assureur::class, 'Assureur', 'IDAssureur');
	}

	public function rendezvous()
	{
		return $this->hasMany(\App\Models\Rendezvou::class, 'fkidPatient', 'ID');
	}

	public function factures()
	{
		return $this->hasMany(\App\Models\Facture::class, 'IDPatient', 'ID');
	}
}
