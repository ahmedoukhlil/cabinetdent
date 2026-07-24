<?php

namespace App\Services;

use App\Models\Medecin;
use App\Models\Facture;
use App\Models\CaisseOperation;
use App\Models\Detailfacturepatient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MedecinFinanceService
{
    /**
     * Récupère les recettes pour un médecin sur une période donnée
     * 
     * @param int $medecinId Identifiant du médecin
     * @param Carbon $dateDebut Date de début de la période
     * @param Carbon $dateFin Date de fin de la période
     * @return array Tableau contenant les détails des recettes et les totaux
     */
    public function getRecettes($medecinId, Carbon $dateDebut, Carbon $dateFin)
    {
        // Récupérer toutes les factures où ce médecin a participé
        $recettes = Detailfacturepatient::join('facture', 'detailfacturepatient.fkidfacture', '=', 'facture.Idfacture')
            ->where('detailfacturepatient.fkidMedecin', $medecinId)
            ->whereBetween('facture.DtFacture', [$dateDebut, $dateFin])
            ->select(
                DB::raw('DATE(facture.DtFacture) as date'),
                DB::raw('SUM(detailfacturepatient.PrixFacture * detailfacturepatient.Quantite) as montant'),
                'facture.Nfacture as reference',
                DB::raw('COUNT(DISTINCT facture.Idfacture) as nombre_factures')
            )
            ->groupBy('date', 'facture.Nfacture')
            ->orderBy('date', 'asc')
            ->get();
            
        // Préparer les données pour le graphique (somme par jour)
        $recettesParJour = $recettes->groupBy('date')
            ->map(function ($items) {
                return $items->sum('montant');
            });
            
        return [
            'details' => $recettes,
            'par_jour' => $recettesParJour,
            'total' => $recettes->sum('montant')
        ];
    }
    
    /**
     * Récupère les dépenses pour un médecin sur une période donnée
     * 
     * @param int $medecinId Identifiant du médecin
     * @param Carbon $dateDebut Date de début de la période
     * @param Carbon $dateFin Date de fin de la période
     * @return array Tableau contenant les détails des dépenses et les totaux
     */
    public function getDepenses($medecinId, Carbon $dateDebut, Carbon $dateFin)
    {
        // Récupérer toutes les opérations de caisse associées à ce médecin
        $depenses = CaisseOperation::where('fkidTiers', $medecinId)
            ->where('entreEspece', '<', 'retraitEspece') // Seulement les opérations de dépense
            ->whereBetween('dateoper', [$dateDebut, $dateFin])
            ->select(
                DB::raw('DATE(dateoper) as date'),
                'retraitEspece as montant',
                'designation',
                'cle as reference'
            )
            ->orderBy('date', 'asc')
            ->get();
            
        // Préparer les données pour le graphique (somme par jour)
        $depensesParJour = $depenses->groupBy('date')
            ->map(function ($items) {
                return $items->sum('montant');
            });
            
        return [
            'details' => $depenses,
            'par_jour' => $depensesParJour,
            'total' => $depenses->sum('montant')
        ];
    }
    
    /**
     * Récupère les statistiques pour un médecin sur une période donnée
     * 
     * @param int $medecinId Identifiant du médecin
     * @param Carbon $dateDebut Date de début de la période
     * @param Carbon $dateFin Date de fin de la période
     * @return array Statistiques diverses
     */
    public function getStatistiques($medecinId, Carbon $dateDebut, Carbon $dateFin)
    {
        // Nombre de patients traités par ce médecin dans la période
        $nbPatients = Detailfacturepatient::join('facture', 'detailfacturepatient.fkidfacture', '=', 'facture.Idfacture')
            ->where('detailfacturepatient.fkidMedecin', $medecinId)
            ->whereBetween('facture.DtFacture', [$dateDebut, $dateFin])
            ->distinct('facture.IDPatient')
            ->count('facture.IDPatient');
            
        // Nombre d'actes réalisés par ce médecin
        $nbActes = Detailfacturepatient::join('facture', 'detailfacturepatient.fkidfacture', '=', 'facture.Idfacture')
            ->where('detailfacturepatient.fkidMedecin', $medecinId)
            ->whereBetween('facture.DtFacture', [$dateDebut, $dateFin])
            ->count();
            
        // Montant moyen par acte
        $montantMoyenParActe = Detailfacturepatient::join('facture', 'detailfacturepatient.fkidfacture', '=', 'facture.Idfacture')
            ->where('detailfacturepatient.fkidMedecin', $medecinId)
            ->whereBetween('facture.DtFacture', [$dateDebut, $dateFin])
            ->avg(DB::raw('detailfacturepatient.PrixFacture * detailfacturepatient.Quantite'));
        
        return [
            'nb_patients' => $nbPatients,
            'nb_actes' => $nbActes,
            'montant_moyen_par_acte' => $montantMoyenParActe
        ];
    }
    
    /**
     * Génère un rapport complet des finances pour un médecin sur une période donnée
     * 
     * @param int $medecinId Identifiant du médecin
     * @param Carbon $dateDebut Date de début de la période
     * @param Carbon $dateFin Date de fin de la période
     * @return array Rapport complet des finances
     */
    public function generateRapport($medecinId, Carbon $dateDebut, Carbon $dateFin)
    {
        $medecin = Medecin::findOrFail($medecinId);
        
        $recettes = $this->getRecettes($medecinId, $dateDebut, $dateFin);
        $depenses = $this->getDepenses($medecinId, $dateDebut, $dateFin);
        $stats = $this->getStatistiques($medecinId, $dateDebut, $dateFin);
        
        $bilan = $recettes['total'] - $depenses['total'];
        
        return [
            'medecin' => $medecin,
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
            ],
            'recettes' => $recettes,
            'depenses' => $depenses,
            'statistiques' => $stats,
            'bilan' => $bilan
        ];
    }
    
    /**
     * Exporte les données financières au format PDF (à implémenter)
     * 
     * @param array $data Données financières à exporter
     * @return \Illuminate\Http\Response
     */
    public function exportPDF($data)
    {
        // À implémenter avec une bibliothèque comme DomPDF
        return null;
    }
    
    /**
     * Exporte les données financières au format Excel (à implémenter)
     * 
     * @param array $data Données financières à exporter
     * @return \Illuminate\Http\Response
     */
    public function exportExcel($data)
    {
        // À implémenter avec une bibliothèque comme PhpSpreadsheet
        return null;
    }
}