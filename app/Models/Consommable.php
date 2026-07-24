<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Consommable
 * 
 * @property int $idConsommables
 * @property string $Libelle
 * @property float $Quantite
 * @property float $PrixUnit
 * @property float $SousTotal
 * @property Carbon|null $DtAchat
 * @property float $fkidFournisseur
 * @property int $fkidUser
 * @property string|null $Designation
 * @property float $fkidFacturePat
 * @property int $IscommandePat
 * @property int $fkidProduit
 * @property string $NumfactFourniss
 *
 * @package App\Models
 */
class Consommable extends Model
{
	protected $table = 'consommables';
	protected $primaryKey = 'idConsommables';
	public $timestamps = false;

	protected $casts = [
		'Quantite' => 'float',
		'PrixUnit' => 'float',
		'SousTotal' => 'float',
		'DtAchat' => 'datetime',
		'fkidFournisseur' => 'float',
		'fkidUser' => 'int',
		'fkidFacturePat' => 'float',
		'IscommandePat' => 'int',
		'fkidProduit' => 'int'
	];

	protected $fillable = [
		'Libelle',
		'Quantite',
		'PrixUnit',
		'SousTotal',
		'DtAchat',
		'fkidFournisseur',
		'fkidUser',
		'Designation',
		'fkidFacturePat',
		'IscommandePat',
		'fkidProduit',
		'NumfactFourniss'
	];
}
