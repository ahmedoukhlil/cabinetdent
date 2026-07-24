<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Patient;
use App\Models\Medecin;
use App\Models\Acte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    public function create()
    {
        return view('consultations.create');
    }

    public function show($id)
    {
        $facture = Facture::with(['patient', 'details.acte'])->findOrFail($id);
        return view('consultations.show', compact('facture'));
    }

    public function showReceipt($factureId)
    {
        try {
            \Log::info('Tentative d\'accès au reçu de consultation', ['facture_id' => $factureId]);
            
            // Utiliser le cache pour les données du cabinet
            $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 3600, function() {
                $user = Auth::user();
                return [
                    'NomCabinet' => $user->cabinet->NomCabinet ?? 'SysMedical',
                    'Adresse' => $user->cabinet->Adresse ?? '',
                    'Telephone' => $user->cabinet->Telephone ?? ''
                ];
            });

            // Précharger toutes les relations nécessaires en une seule requête
            $facture = Facture::with([
                'patient',
                'medecin',
                'details.acte',
                'assureur',
                'rendezVous' => function($query) {
                    $query->select('IDRdv', 'OrdreRDV', 'fkidFacture', 'HeureRdv', 'dtPrevuRDV', 'fkidMedecin');
                }
            ])->findOrFail($factureId);

            \Log::info('Facture trouvée', ['facture' => $facture->toArray()]);

            // Mettre en cache la conversion en lettres (1h)
            $facture->en_lettres = cache()->remember('facture_lettres_' . $factureId, 3600, function() use ($facture) {
                return $this->numberToWords($facture->TotFacture ?? 0);
            });

            return view('consultations.receipt', compact('facture', 'cabinet'));
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage du reçu', [
                'facture_id' => $factureId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function showFacturePatient($factureId)
    {
        // Utiliser le cache pour les données du cabinet (24h car rarement modifiées)
        $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 86400, function() {
            $user = Auth::user();
            return [
                'NomCabinet' => $user->cabinet->NomCabinet ?? 'SysMedical',
                'Adresse' => $user->cabinet->Adresse ?? '',
                'Telephone' => $user->cabinet->Telephone ?? ''
            ];
        });

        // Charger la facture avec TOUS ses détails (actes, médicaments, analyses, radios)
        // Ne pas utiliser de cache pour éviter les problèmes de données obsolètes
        $facture = Facture::where('Idfacture', $factureId)
            ->with([
                'patient' => function($query) {
                    $query->select('ID', 'IdentifiantPatient', 'NomContact', 'Telephone1', 'IdentifiantAssurance', 'Assureur');
                },
                'medecin' => function($query) {
                    $query->select('idMedecin', 'Nom', 'Contact');
                },
                'details' => function($query) {
                    // Charger TOUS les détails sans restriction de type
                    $query->orderBy('idDetfacture');
                },
                'patient.assureur' => function($query) {
                    $query->select('IDAssureur', 'LibAssurance');
                }
            ])
            ->firstOrFail();

        // Vérifier que tous les détails sont bien chargés
        if (!$facture->patient) {
            // Recharger la facture si le patient est manquant
            $facture = Facture::with([
                'patient' => function($query) {
                    $query->select('ID', 'IdentifiantPatient', 'NomContact', 'Telephone1', 'IdentifiantAssurance', 'Assureur');
                },
                'medecin' => function($query) {
                    $query->select('idMedecin', 'Nom', 'Contact');
                },
                'details', // Charger tous les détails
                'patient.assureur'
            ])->findOrFail($factureId);
        }
        
        // Vider le cache pour cette facture pour forcer le rechargement
        cache()->forget('facture_' . $factureId);
        cache()->forget('facture_montants_' . $factureId);
        cache()->forget('facture_lettres_' . $factureId);

        // Calculer le total réel à partir des détails
        // Inclut TOUS les types : IsAct=1 (Actes), IsAct=2 (Médicaments), IsAct=3 (Analyses), IsAct=4 (Radios)
        // Vérifier que tous les détails sont bien chargés
        $totalReel = $facture->details->sum(function($detail) {
            $prix = floatval($detail->PrixFacture ?? 0);
            $quantite = floatval($detail->Quantite ?? 0);
            $montant = $prix * $quantite;
            return $montant;
        });
        
        // Debug : vérifier le nombre de détails chargés
        \Log::info('Calcul total facture', [
            'facture_id' => $factureId,
            'nb_details' => $facture->details->count(),
            'total_reel' => $totalReel,
            'details_types' => $facture->details->groupBy('IsAct')->map->count()
        ]);
        
        // Recalculer TotalPEC et TotalfactPatient si ISTP == 1
        $txpec = $facture->TXPEC ?? 0;
        $totalPECReel = $facture->ISTP == 1 ? ($totalReel * $txpec) : 0;
        $totalfactPatientReel = $facture->ISTP == 1 ? ($totalReel - $totalPECReel) : $totalReel;
        
        // Utiliser les totaux réels calculés ou ceux de la base de données
        $totalFacture = $totalReel > 0 ? $totalReel : ($facture->TotFacture ?? 0);
        
        // Pré-calculer et mettre en cache les montants (1h)
        $montants = cache()->remember('facture_montants_' . $factureId, 3600, function() use ($facture, $totalFacture, $totalfactPatientReel, $totalPECReel) {
            $totalPEC = $totalPECReel > 0 ? $totalPECReel : ($facture->TotalPEC ?? 0);
            $restePEC = $totalPEC - ($facture->ReglementPEC ?? 0);
            $restePatient = $facture->ISTP == 1 
                ? ($totalfactPatientReel - ($facture->TotReglPatient ?? 0))
                : ($totalFacture - ($facture->TotReglPatient ?? 0));

            return [
                'restePEC' => $restePEC,
                'restePatient' => $restePatient
            ];
        });

        // Mettre en cache la conversion en lettres (1h) avec le total réel
        $facture->en_lettres = cache()->remember('facture_lettres_' . $factureId, 3600, function() use ($totalFacture) {
            return $this->numberToWords($totalFacture);
        });

        // Ajouter les montants calculés à la facture
        $facture->restePEC = $montants['restePEC'];
        $facture->restePatient = $montants['restePatient'];

        // Forcer le type à "Facture" par défaut (toujours afficher une facture)
        $facture->Type = 'Facture';

        // Récupérer l'utilisateur connecté
        $currentUser = Auth::user();

        return view('consultations.facture-patient', compact('facture', 'cabinet', 'currentUser'));
    }

    /**
     * Afficher l'ordonnance pour une consultation/facture
     */
    public function showOrdonnance($factureId)
    {
        // Charger la facture avec les détails et le patient
        $consultation = Facture::where('Idfacture', $factureId)
            ->with([
                'patient' => function($query) {
                    $query->select('ID', 'IdentifiantPatient', 'NomContact', 'Telephone1', 'Age');
                },
                'medecin' => function($query) {
                    $query->select('idMedecin', 'Nom', 'Contact', 'Specialite');
                },
                'details' => function($query) {
                    // Charger uniquement les médicaments (IsAct = 2, 3, 4)
                    $query->whereIn('IsAct', [2, 3, 4])
                          ->with(['medicament'])
                          ->orderBy('idDetfacture');
                }
            ])
            ->firstOrFail();

        // Préparer les médicaments avec leurs informations
        $medications = $consultation->details->map(function($detail) {
            $medicamentInfo = new \stdClass();
            
            // Nom du médicament
            if ($detail->medicament) {
                $medicamentInfo->LibMedicament = $detail->medicament->LibelleMedic ?? $detail->Actes;
            } else {
                $medicamentInfo->LibMedicament = $detail->Actes;
            }
            
            // Posologie (peut être ajoutée manuellement ou depuis un autre champ)
            // Pour l'instant, on affiche la quantité
            $quantite = $detail->Quantite ?? 1;
            $medicamentInfo->Posologie = $quantite > 1 ? "Quantité : $quantite" : '';
            $medicamentInfo->Quantite = $quantite;
            
            return $medicamentInfo;
        });

        // Utiliser le cache pour les données du cabinet
        $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 86400, function() {
            $user = Auth::user();
            return [
                'NomCabinet' => $user->cabinet->NomCabinet ?? 'SysMedical',
                'Adresse' => $user->cabinet->Adresse ?? 'Tevragh-Zeina PK Exit N°58',
                'Telephone' => $user->cabinet->Telephone ?? '32 77 48 97 – 43 42 15 56'
            ];
        });

        // Ajouter un champ Poids à la consultation (s'il existe dans votre base)
        $consultation->Poids = $consultation->Poids ?? '';

        return view('consultations.ordonnance', compact('consultation', 'medications', 'cabinet'));
    }

    // Helper simple pour montant en lettres (à remplacer par votre propre logique si besoin)
    private function numberToWords($number)
    {
        // Utilisez un package ou une fonction plus complète si besoin
        $f = new \NumberFormatter("fr", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }
} 