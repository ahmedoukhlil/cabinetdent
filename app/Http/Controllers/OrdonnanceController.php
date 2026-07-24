<?php

namespace App\Http\Controllers;

use App\Models\Ordonnanceref;
use App\Models\Infocabinet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class OrdonnanceController extends Controller
{
    /**
     * Afficher/Imprimer une ordonnance (identique à la logique du reçu de consultation)
     */
    public function print($id)
    {
        try {
            \Log::info('Tentative d\'accès à l\'ordonnance', ['ordonnance_id' => $id]);
            
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

            // Précharger toutes les relations nécessaires en une seule requête
            $ordonnance = Ordonnanceref::with([
                'patient',
                'prescripteur',
                'ordonnances' => function($query) {
                    $query->orderBy('NumordreOrd');
                },
                'cabinet'
            ])->findOrFail($id);

            \Log::info('Ordonnance trouvée', [
                'ordonnance_id' => $ordonnance->id,
                'fkidCabinet' => $ordonnance->fkidCabinet,
                'user_fkidcabinet' => Auth::user()->fkidcabinet ?? 'null'
            ]);

            // Retourner la vue HTML (comme pour les consultations)
            return view('ordonnances.print', compact('ordonnance', 'cabinet'));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage de l\'ordonnance', [
                'ordonnance_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Télécharger une ordonnance en PDF
     */
    public function download($id)
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

            $ordonnance = Ordonnanceref::with([
                'patient',
                'prescripteur',
                'ordonnances' => function($query) {
                    $query->orderBy('NumordreOrd');
                },
                'cabinet'
            ])->findOrFail($id);

            $pdf = PDF::loadView('ordonnances.print', compact('ordonnance', 'cabinet'));

            return $pdf->download('ordonnance_' . $ordonnance->refOrd . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Erreur téléchargement ordonnance', [
                'ordonnance_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors du téléchargement de l\'ordonnance.');
        }
    }

    /**
     * Afficher une ordonnance vierge
     */
    public function blank()
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

            // Créer un objet ordonnance vide pour la vue
            $ordonnance = (object) [
                'TypeOrdonnance' => 'ORDONNANCE',
                'refOrd' => '',
                'patient' => null,
                'prescripteur' => null,
                'ordonnances' => collect([]),
                'dtPrescript' => now()
            ];

            return view('ordonnances.print', compact('ordonnance', 'cabinet'));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage de l\'ordonnance vierge', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
