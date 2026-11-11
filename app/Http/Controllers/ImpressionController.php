<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\Facture;
use App\Models\FichierPatient;
use Illuminate\Support\Facades\Storage;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\PlanTraitement;
use App\Services\CabinetExportService;
use App\Services\StatistiquesService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Documents imprimables (HTML optimisé pour l'impression ou l'enregistrement en PDF par le navigateur). */
class ImpressionController extends Controller
{
    public function facture(Facture $facture)
    {
        // Un brouillon s'imprime comme devis (sans numéro de facture ni règlements).
        abort_if($facture->statut === 'brouillon' && $facture->lignes()->doesntExist(), 404);
        $facture->load(['patient', 'assureur', 'dentiste', 'lignes', 'cabinet']);

        return response()->view('impression.facture', compact('facture'))->header('Cache-Control', 'private, no-store');
    }

    public function dossier(Patient $patient)
    {
        $patient->load('cabinet');
        $dents = $patient->dents()->get()->keyBy('numero');
        $planActif = app(\App\Services\DossierDentaireService::class)->planActif($patient)?->load('lignes');
        $dentsPrevues = $planActif ? $planActif->lignes->where('statut', 'prevu')->pluck('dents')->filter()->flatten()->all() : [];

        return response()->view('impression.dossier', [
            'patient' => $patient,
            'dents' => $dents,
            // Mêmes couleurs que l'odontogramme anatomique de l'écran.
            'conditions' => \App\Support\Odontologie::conditionsGraphe($dents, $dentsPrevues),
            'plans' => PlanTraitement::with('lignes')->where('patient_id', $patient->id)->where('statut', '!=', 'annule')->latest('id')->get(),
            'fichiers' => FichierPatient::where('patient_id', $patient->id)->latest('date_examen')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Fichier clinique : servi uniquement via cette route authentifiée et limitée au cabinet. */
    public function fichier(FichierPatient $fichier)
    {
        abort_unless(Storage::disk('local')->exists($fichier->chemin), 404);
        $nom = str_replace(['"', "\\"], '', $fichier->nom_origine);

        return Storage::disk('local')->response($fichier->chemin, $nom, [
            'Content-Type' => $fichier->mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function recu(Paiement $paiement)
    {
        $paiement->load(['facture.assureur', 'patient', 'auteur', 'cabinet']);

        return response()->view('impression.recu', compact('paiement'))->header('Cache-Control', 'private, no-store');
    }

    public function statistiques(Request $request, StatistiquesService $service)
    {
        $date = function (?string $valeur, Carbon $defaut) {
            try {
                return Carbon::createFromFormat('Y-m-d', (string) $valeur)->startOfDay();
            } catch (\Throwable) {
                return $defaut;
            }
        };
        $du = $date($request->query('du'), today()->startOfMonth());
        $au = $date($request->query('au'), today());
        if ($au->lt($du)) {
            $au = $du->copy();
        }

        return response()->view('impression.statistiques', [
            ...$service->parPraticien($request->user(), $du, $au),
            'du' => $du,
            'au' => $au,
            'cabinet' => $request->user()->cabinet,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request, CabinetExportService $service)
    {
        $chemin = $service->exporter($request->user());
        $nom = 'sysdental-'.Str::slug($request->user()->cabinet->nom).'-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($chemin, $nom, ['Content-Type' => 'application/zip', 'Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }

    public function caisse(Request $request)
    {
        $date = function (?string $valeur) {
            try {
                return Carbon::createFromFormat('Y-m-d', (string) $valeur);
            } catch (\Throwable) {
                return today();
            }
        };
        $du = $date($request->query('du'))->startOfDay();
        $au = $date($request->query('au'))->endOfDay();

        $operations = Paiement::with(['patient', 'facture'])
            ->whereBetween('created_at', [$du, $au])
            ->when($request->integer('dentiste'), fn ($q, $id) => $q->where('dentiste_id', $id))
            ->orderBy('id')
            ->get();

        $depenses = Depense::whereDate('date_depense', '>=', $du->toDateString())->whereDate('date_depense', '<=', $au->toDateString())
            ->when($request->integer('dentiste'), fn ($q, $id) => $q->where('dentiste_id', $id))
            ->orderBy('date_depense')->orderBy('id')
            ->get();

        return response()->view('impression.caisse', [
            'operations' => $operations,
            'depenses' => $depenses,
            'du' => $du,
            'au' => $au,
            'cabinet' => $request->user()->cabinet,
        ])->header('Cache-Control', 'private, no-store');
    }
}
