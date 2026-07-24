<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CaisseOperation;
use App\Models\Facture;
use App\Models\Patient;
use App\Models\Medecin;
use App\Models\RefTypePaiement;
use Illuminate\Support\Facades\Auth;

class ReglementFactureController extends Controller
{
    public function showReceipt($operationId)
    {
        $operation = CaisseOperation::findOrFail($operationId);
        $facture = Facture::find($operation->fkidfacturebord);
        $patient = Patient::find($operation->fkidTiers);
        $medecin = Medecin::find($operation->fkidmedecin);
        $mode = RefTypePaiement::find($operation->fkidtypePaie);
        $user = Auth::user();
        $cabinet = $user->cabinet ?? null;

        // Définir le type de règlement (P pour paiement, R pour remboursement)
        $facture->TypeReglement = $operation->MontantOperation < 0 ? 'R' : 'P';

        // Générer le montant en lettres
        $operation->MontantEnLettre = $this->numberToWords(abs($operation->MontantOperation));

        return view('factures.recu-reglement', compact('operation', 'facture', 'patient', 'medecin', 'mode', 'user', 'cabinet'));
    }

    // Helper pour montant en lettres
    private function numberToWords($number)
    {
        $f = new \NumberFormatter("fr", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }
} 