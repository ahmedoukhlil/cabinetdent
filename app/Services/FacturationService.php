<?php

namespace App\Services;

use App\Models\Acte;
use App\Models\Detailfacturepatient;
use App\Models\Facture;

class FacturationService
{
    public function ajouterActeAFacture(
        int $factureId,
        int $acteId,
        float $prixFacture,
        int $quantite,
        ?string $numDent = null
    ): Detailfacturepatient {
        $acte = Acte::find($acteId);

        $detail = Detailfacturepatient::create([
            'fkidfacture' => $factureId,
            'DtAjout' => now(),
            'Actes' => $acte ? $acte->Acte : null,
            'PrixRef' => $acte ? $acte->PrixRef : $prixFacture,
            'PrixFacture' => $prixFacture,
            'Quantite' => $quantite,
            'fkidacte' => $acteId,
            'Dents' => $numDent ?? 'Dent',
        ]);

        $facture = Facture::find($factureId);
        $prixFactureActe = $prixFacture * $quantite;
        $txpec = $facture->TXPEC ?? 0;
        $nouveauTotFacture = ($facture->TotFacture ?? 0) + $prixFactureActe;
        $montantPEC = $prixFactureActe * $txpec;
        $totalPEC = ($facture->TotalPEC ?? 0) + $montantPEC;
        $totalfactPatient = $nouveauTotFacture - $totalPEC;

        $facture->TotFacture = $nouveauTotFacture;
        $facture->TotalPEC = $totalPEC;
        $facture->TotalfactPatient = $totalfactPatient;
        $facture->save();

        return $detail;
    }
}
