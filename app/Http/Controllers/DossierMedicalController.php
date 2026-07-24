<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Fichetraitement;
use App\Models\Patient;
use App\Models\DossierMedical;
use App\Models\ConsultationMedicale;
use App\Models\AnalysePatient;
use App\Models\Infocabinet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PDF;

class DossierMedicalController extends Controller
{
    /**
     * Afficher/Imprimer la fiche médicale d'un patient pour une facture
     */
    public function print($factureId)
    {
        try {
            \Log::info('Tentative d\'accès à la fiche médicale', ['facture_id' => $factureId]);
            
            // Utiliser le cache pour les données du cabinet
            $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 3600, function() {
                $user = Auth::user();
                return [
                    'NomCabinet' => $user->cabinet->NomCabinet ?? null,
                    'Adresse' => $user->cabinet->Adresse ?? null,
                    'Telephone' => $user->cabinet->Telephone ?? null,
                    'Email' => $user->cabinet->Email ?? null
                ];
            });

            // Charger la facture avec ses relations
            $facture = Facture::with([
                'patient',
                'medecin',
                'reglements'
            ])->where('fkidCabinet', Auth::user()->fkidcabinet)
              ->findOrFail($factureId);

            // Charger toutes les fiches de traitement de cette facture
            $fichesTraitement = Fichetraitement::where('fkidfacture', $factureId)
                ->where('IsSupprimer', 0)
                ->orderBy('Ordre', 'asc')
                ->orderBy('dateTraite', 'asc')
                ->get();

            // Calculer le montant total payé pour cette facture
            $montantPaye = $facture->TotReglPatient ?? 0;

            \Log::info('Fiche médicale chargée', [
                'facture_id' => $facture->Idfacture,
                'patient_id' => $facture->IDPatient,
                'nb_fiches' => $fichesTraitement->count()
            ]);

            return view('dossier-medical.print', compact('facture', 'fichesTraitement', 'cabinet', 'montantPaye'));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage de la fiche médicale', [
                'facture_id' => $factureId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Imprimer le dossier médical complet d'un patient
     */
    public function printPatient($patientId)
    {
        $patient = Patient::where('fkidcabinet', Auth::user()->fkidcabinet)
            ->findOrFail($patientId);

        $cabinet = Infocabinet::first();

        $dossier = DossierMedical::where('fkidPatient', $patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->first();

        $consultations = ConsultationMedicale::where('fkidPatient', $patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->with('medecin')
            ->orderBy('date_consultation', 'desc')
            ->get();

        $analyses = AnalysePatient::where('fkidPatient', $patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->orderBy('date_analyse', 'desc')
            ->get()
            ->map(function ($a) {
                $a->url = Storage::url($a->fichier_path);
                return $a;
            });

        return view('dossier-medical.print-patient', compact(
            'patient', 'cabinet', 'dossier', 'consultations', 'analyses'
        ));
    }

    /**
     * Télécharger la fiche médicale en PDF
     */
    public function download($factureId)
    {
        try {
            // Utiliser le cache pour les données du cabinet
            $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 3600, function() {
                $user = Auth::user();
                return [
                    'NomCabinet' => $user->cabinet->NomCabinet ?? null,
                    'Adresse' => $user->cabinet->Adresse ?? null,
                    'Telephone' => $user->cabinet->Telephone ?? null,
                    'Email' => $user->cabinet->Email ?? null
                ];
            });

            $facture = Facture::with([
                'patient',
                'medecin',
                'reglements'
            ])->where('fkidCabinet', Auth::user()->fkidcabinet)
              ->findOrFail($factureId);

            $fichesTraitement = Fichetraitement::where('fkidfacture', $factureId)
                ->where('IsSupprimer', 0)
                ->orderBy('Ordre', 'asc')
                ->orderBy('dateTraite', 'asc')
                ->get();

            $montantPaye = $facture->TotReglPatient ?? 0;

            $pdf = PDF::loadView('dossier-medical.print', compact('facture', 'fichesTraitement', 'cabinet', 'montantPaye'));

            return $pdf->download('fiche-medicale-' . $facture->Nfacture . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Erreur téléchargement fiche médicale', [
                'facture_id' => $factureId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors du téléchargement de la fiche médicale.');
        }
    }
}

