<?php

namespace App\Http\Controllers;

use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CaisseController extends Controller
{
    public function printEtatCaisse($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $user = Auth::user();
        
        // Récupérer les opérations du jour
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet)
            ->whereDate('dateoper', $date);

        if ($user->IdClasseUser == 1) { // Si c'est une secrétaire
            $query->where('fkiduser', $user->Iduser);
        } elseif ($user->IdClasseUser == 2) { // Si c'est un médecin
            $query->where('fkidmedecin', $user->fkidmedecin);
        }

        // Seul le médecin propriétaire peut voir les dépenses
        if (($user->IdClasseUser ?? 0) != 3) {
            $query->where('retraitEspece', 0);
        }

        $operations = $query->orderBy('dateoper', 'asc')->get();

        // Calculer les totaux
        $totalRecettes = $operations->sum('entreEspece');
        $totalDepenses = $operations->sum('retraitEspece');
        $solde = $totalRecettes - $totalDepenses;

        // Calculer les totaux par mode de paiement
        $typesPaiement = $operations->pluck('TypePAie')->unique();
        $totauxParModePaiement = collect($typesPaiement)->mapWithKeys(function($type) use ($operations) {
            $recettes = $operations->where('TypePAie', $type)
                ->where('entreEspece', '>', 0)
                ->sum('MontantOperation');
            
            $depenses = $operations->where('TypePAie', $type)
                ->where('retraitEspece', '>', 0)
                ->sum('MontantOperation');

            return [$type => [
                'recettes' => $recettes,
                'depenses' => $depenses,
                'solde' => $recettes - $depenses
            ]];
        })->toArray();

        // Récupérer les informations du cabinet
        $cabinet = $user->cabinet;

        return view('caisse.etat-journalier', [
            'date' => $date,
            'operations' => $operations,
            'totalRecettes' => $totalRecettes,
            'totalDepenses' => $totalDepenses,
            'solde' => $solde,
            'totauxParModePaiement' => $totauxParModePaiement,
            'cabinet' => $cabinet,
            'user' => $user
        ]);
    }
} 