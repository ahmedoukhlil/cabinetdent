<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\PlanTraitementDentaire;
use App\Services\FileAttenteService;
use Illuminate\Support\Facades\Auth;

class PatientEspaceController extends Controller
{
    /**
     * Tableau de bord — résumé rapide, liens vers les autres pages.
     */
    public function dashboard()
    {
        $patient = Auth::guard('patient')->user();

        $planEnCours = PlanTraitementDentaire::forPatient($patient->ID)
            ->whereIn('statut', ['planifie', 'en_cours'])
            ->count();

        $facturesImpayees = Facture::where('IDPatient', $patient->ID)
            ->whereColumn('TotReglPatient', '<', 'TotalfactPatient')
            ->count();

        return view('patient-auth.dashboard', compact('patient', 'planEnCours', 'facturesImpayees'));
    }

    /**
     * Plan de traitement du patient — lecture seule, groupé par statut.
     */
    public function planTraitement()
    {
        $patient = Auth::guard('patient')->user();

        $lignes = PlanTraitementDentaire::forPatient($patient->ID)
            ->with('medecin')
            ->orderByRaw("FIELD(statut, 'en_cours', 'planifie', 'termine')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('patient-auth.plan-traitement', compact('patient', 'lignes'));
    }

    /**
     * Liste des factures du patient — lecture seule.
     */
    public function factures()
    {
        $patient = Auth::guard('patient')->user();

        $factures = Facture::where('IDPatient', $patient->ID)
            ->orderBy('DtFacture', 'desc')
            ->get();

        return view('patient-auth.factures', compact('patient', 'factures'));
    }

    /**
     * Détail d'une facture précise — vérifie que la facture appartient bien
     * au patient connecté, pour ne jamais exposer la facture d'un autre.
     */
    public function factureDetail(int $id)
    {
        $patient = Auth::guard('patient')->user();

        $facture = Facture::where('IDPatient', $patient->ID)
            ->with(['details', 'reglements', 'medecin'])
            ->findOrFail($id);

        return view('patient-auth.facture-detail', compact('patient', 'facture'));
    }

    /**
     * Historique des paiements (règlements) du patient — lecture seule,
     * regroupés à partir de ses factures.
     */
    public function paiements()
    {
        $patient = Auth::guard('patient')->user();

        $factures = Facture::where('IDPatient', $patient->ID)
            ->with('reglements')
            ->get();

        $reglements = $factures->flatMap(function ($facture) {
            return $facture->reglements->map(function ($reglement) use ($facture) {
                $reglement->facture_numero = $facture->Nfacture;
                return $reglement;
            });
        })->sortByDesc('dtreglement')->values();

        return view('patient-auth.paiements', compact('patient', 'reglements'));
    }

    /**
     * File d'attente du patient pour son rendez-vous du jour, si applicable.
     * Rafraîchie côté vue via polling (meta refresh JS), sans WebSocket.
     */
    public function fileAttente(FileAttenteService $service)
    {
        $patient = Auth::guard('patient')->user();

        $etat = $service->pourPatientAujourdhui($patient->ID);

        return view('patient-auth.file-attente', compact('patient', 'etat'));
    }
}
