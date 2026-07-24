<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Infocabinet
 * 
 * @property int $idEntete
 * @property string|null $NomCabFr
 * @property string|null $NomCabAr
 * @property string|null $Specialite1Fr
 * @property string|null $Specialite2fr
 * @property string|null $Specialite3Fr
 * @property string|null $Specialite1Ar
 * @property string|null $Specialite2Ar
 * @property string|null $Specialite3Ar
 * @property string|null $AdresseL1AR
 * @property string|null $AdresseL2AR
 * @property string|null $AdresseFr1
 * @property string|null $AdresseFr2
 * @property string|null $ContactAR
 * @property string|null $AdresseMail
 * @property string|null $ContactFR
 * @property string|null $TelephonePublic
 * @property string|null $DRAr
 * @property string|null $DrFr
 *
 * @package App\Models
 */
class Infocabinet extends Model
{
	protected $table = 'infocabinet';
	protected $primaryKey = 'idEntete';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idEntete' => 'int'
	];

	protected $fillable = [
		'NomCabFr',
		'NomCabAr',
		'Specialite1Fr',
		'Specialite2fr',
		'Specialite3Fr',
		'Specialite1Ar',
		'Specialite2Ar',
		'Specialite3Ar',
		'AdresseL1AR',
		'AdresseL2AR',
		'AdresseFr1',
		'AdresseFr2',
		'ContactAR',
		'AdresseMail',
		'ContactFR',
		'TelephonePublic',
		'DrAr',
		'DrFr',
		'logo',
		'piedPage',
	];
}
