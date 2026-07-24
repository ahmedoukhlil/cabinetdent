<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\CaisseOperation;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    public function printHistorique($patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $paymentHistory = CaisseOperation::where('fkidTiers', $patientId)
            ->orderBy('dateoper', 'desc')
            ->get();
        return view('paiement.print-historique', compact('patient', 'paymentHistory'));
    }
} 