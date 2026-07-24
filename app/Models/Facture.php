<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class Facture
 * 
 * @property int $Idfacture
 * @property string|null $Nfacture
 * @property int|null $anneeFacture
 * @property int|null $nordre
 * @property Carbon|null $DtFacture
 * @property int|null $IDPatient
 * @property int|null $ISTP
 * @property int|null $fkidEtsAssurance
 * @property float $TXPEC
 * @property float $TotFacture
 * @property float $TotalPEC
 * @property float $TotalfactPatient
 * @property float $TotReglPatient
 * @property float $ReglementPEC
 * @property string|null $ModeReglement
 * @property string|null $Areglepar
 * @property Carbon|null $DtReglement
 * @property float $fkidbordfacture
 * @property int $ispayerAssureur
 * @property string|null $user
 * @property int $estfacturer
 * @property int $FkidMedecinInitiateur
 * @property float $PartLaboratoire
 * @property float $MontantAffectation
 * @property string $Type
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class Facture extends Model
{
	protected $table = 'facture';
	protected $primaryKey = 'Idfacture';
	public $timestamps = false;

	protected $casts = [
		'anneeFacture' => 'int',
		'nordre' => 'int',
		'DtFacture' => 'datetime',
		'IDPatient' => 'int',
		'ISTP' => 'int',
		'fkidEtsAssurance' => 'int',
		'TXPEC' => 'float',
		'TotFacture' => 'float',
		'TotalPEC' => 'float',
		'TotalfactPatient' => 'float',
		'TotReglPatient' => 'float',
		'ReglementPEC' => 'float',
		'DtReglement' => 'datetime',
		'fkidbordfacture' => 'float',
		'ispayerAssureur' => 'int',
		'estfacturer' => 'int',
		'FkidMedecinInitiateur' => 'int',
		'PartLaboratoire' => 'float',
		'MontantAffectation' => 'float',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'Nfacture',
		'anneeFacture',
		'nordre',
		'DtFacture',
		'IDPatient',
		'ISTP',
		'fkidEtsAssurance',
		'TXPEC',
		'TotFacture',
		'TotalPEC',
		'TotalfactPatient',
		'TotReglPatient',
		'ReglementPEC',
		'ModeReglement',
		'Areglepar',
		'DtReglement',
		'fkidbordfacture',
		'ispayerAssureur',
		'user',
		'estfacturer',
		'FkidMedecinInitiateur',
		'PartLaboratoire',
		'MontantAffectation',
		'Type',
		'fkidCabinet'
	];

	// Relations
	public function patient()
	{
		return $this->belongsTo(Patient::class, 'IDPatient', 'ID');
	}

	public function details()
	{
		return $this->hasMany(Detailfacturepatient::class, 'fkidfacture', 'Idfacture');
	}

	public function medecin()
	{
		return $this->belongsTo(Medecin::class, 'FkidMedecinInitiateur', 'idMedecin');
	}

	public function rendezVous()
	{
		return $this->hasOne(Rendezvou::class, 'fkidFacture', 'Idfacture');
	}

	public function assureur()
	{
		return $this->belongsTo(Assureur::class, 'fkidEtsAssurance', 'IDAssureur');
	}

	public function reglements()
	{
		return $this->hasMany(Reglement::class, 'fkidFactBord', 'Idfacture');
	}

	/**
	 * Générer un numéro de facture unique pour un cabinet donné
	 * 
	 * @param int $cabinetId L'ID du cabinet
	 * @param int|null $annee L'année (par défaut année courante)
	 * @return array ['Nfacture' => string, 'nordre' => int, 'anneeFacture' => int]
	 */
	public static function generateUniqueFactureNumber($cabinetId, $annee = null)
	{
		$annee = $annee ?? Carbon::now()->year;
		$maxRetries = 10;
		$retry = 0;
		
		while ($retry < $maxRetries) {
			try {
				// Utiliser une transaction avec verrouillage pour éviter les race conditions
				return DB::transaction(function () use ($cabinetId, $annee) {
					// Trouver la dernière facture pour ce cabinet et cette année avec verrouillage
					$derniereFacture = self::where('anneeFacture', $annee)
						->where('fkidCabinet', $cabinetId)
						->lockForUpdate() // Verrouiller les lignes pour éviter les conflits
						->orderBy('nordre', 'desc')
						->first();
					
					// Calculer le prochain numéro
					if ($derniereFacture && $derniereFacture->Nfacture) {
						// Extraire le numéro de la dernière facture
						$parts = explode('-', $derniereFacture->Nfacture);
						$numero = intval($parts[0]) + 1;
						$nordre = $derniereFacture->nordre + 1;
					} else {
						$numero = 1;
						$nordre = 1;
					}
					
					$nfacture = $numero . '-' . $annee;
					
					// Vérifier l'unicité du numéro de facture
					$exists = self::where('Nfacture', $nfacture)
						->where('fkidCabinet', $cabinetId)
						->exists();
					
					if ($exists) {
						// Si le numéro existe déjà, incrémenter et réessayer
						$numero++;
						$nordre++;
						$nfacture = $numero . '-' . $annee;
						
						// Vérifier à nouveau
						$exists = self::where('Nfacture', $nfacture)
							->where('fkidCabinet', $cabinetId)
							->exists();
						
						if ($exists) {
							throw new \Exception('Numéro de facture déjà existant: ' . $nfacture);
						}
					}
					
					return [
						'Nfacture' => $nfacture,
						'nordre' => $nordre,
						'anneeFacture' => $annee
					];
				});
				
			} catch (\Exception $e) {
				$retry++;
				
				if ($retry >= $maxRetries) {
					throw new \Exception('Impossible de générer un numéro de facture unique après ' . $maxRetries . ' tentatives: ' . $e->getMessage());
				}
				
				// Attendre un peu avant de réessayer (éviter les collisions)
				usleep(100000); // 100ms
			}
		}
		
		throw new \Exception('Impossible de générer un numéro de facture unique après ' . $maxRetries . ' tentatives');
	}

	/**
	 * Grouper les détails de facture par type d'acte
	 * Retourne un tableau avec les sections : Actes médicaux, Médicaments, Analyses, Radios
	 */
	public function getDetailsGroupesParType()
	{
		$details = $this->details()
			->with(['acte.typeActe', 'medicament'])
			->get();
		
		$groupes = [
			'Actes médicaux' => [],
			'Médicaments' => [],
			'Analyses' => [],
			'Radios' => [],
			'Autres' => []
		];

		foreach ($details as $detail) {
			$section = 'Autres';
			
			if ($detail->IsAct == 1 && $detail->acte) {
				// C'est un acte
				$typeActe = $detail->acte->typeActe->Type ?? null;
				if ($typeActe) {
					$typeActeLower = strtolower($typeActe);
					if (strpos($typeActeLower, 'médicament') !== false || strpos($typeActeLower, 'medicament') !== false) {
						$section = 'Actes médicaux';
					} elseif (strpos($typeActeLower, 'analyse') !== false) {
						$section = 'Analyses';
					} elseif (strpos($typeActeLower, 'radio') !== false) {
						$section = 'Radios';
					} else {
						$section = 'Actes médicaux'; // Par défaut pour les actes
					}
				} else {
					$section = 'Actes médicaux'; // Par défaut si pas de type
				}
			} elseif ($detail->IsAct == 2 && $detail->medicament) {
				// C'est un médicament
				$section = 'Médicaments';
			} elseif ($detail->IsAct == 3 && $detail->medicament) {
				// C'est une analyse
				$section = 'Analyses';
			} elseif ($detail->IsAct == 4 && $detail->medicament) {
				// C'est une radio
				$section = 'Radios';
			}

			$groupes[$section][] = $detail;
		}

		// Retirer les sections vides
		return array_filter($groupes, function($details) {
			return count($details) > 0;
		});
	}

	/**
	 * Vérifie si la facture est complètement payée
	 * 
	 * @return bool
	 */
	public function estCompletementPayee()
	{
		// Patient non assuré : TotFacture doit être <= TotReglPatient
		if ($this->ISTP == 0) {
			return ($this->TotFacture ?? 0) <= ($this->TotReglPatient ?? 0);
		}
		
		// Patient assuré : TotalfactPatient <= TotReglPatient ET TotalPEC <= ReglementPEC
		$patientPaye = ($this->TotalfactPatient ?? 0) <= ($this->TotReglPatient ?? 0);
		$pecPayee = ($this->TotalPEC ?? 0) <= ($this->ReglementPEC ?? 0);
		
		return $patientPaye && $pecPayee;
	}
}
