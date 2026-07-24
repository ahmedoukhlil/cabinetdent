<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rendezvou;
use App\Models\Patient;
use App\Models\Consultation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PatientInterfaceController extends Controller
{
    /**
     * Affiche les rendez-vous d'un patient via token
     */
    public function showRendezVous($token)
    {
        try {
            // Décoder le token pour obtenir l'ID du patient
            $patientId = $this->decodeToken($token);
            
            if (!$patientId) {
                return view('patient.error', ['message' => 'Token invalide ou expiré']);
            }

            // Extraire la date et l'ID du médecin du token
            $dateToken = $this->getDateFromToken($token);
            $medecinIdFromToken = $this->getMedecinIdFromToken($token);
            
            // Debug pour voir les dates
            \Log::info('Date Token: ' . $dateToken);
            \Log::info('Date Aujourd\'hui: ' . now()->format('Y-m-d'));

            $patient = Patient::find($patientId);
            
            if (!$patient) {
                return view('patient.error', ['message' => 'Patient non trouvé']);
            }

            // Récupérer tous les rendez-vous du patient
            $rendezVous = Rendezvou::with(['medecin', 'cabinet'])
                ->where('fkidPatient', $patientId)
                ->orderBy('dtPrevuRDV', 'desc')
                ->get();

            // Récupérer le rendez-vous spécifique pour la date du token et le médecin
            $query = Rendezvou::with(['medecin', 'cabinet'])
                ->where('fkidPatient', $patientId)
                ->whereDate('dtPrevuRDV', $dateToken)
                ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé']);
            
            // Si l'ID du médecin est dans le token, filtrer par médecin
            if ($medecinIdFromToken) {
                $query->where('fkidMedecin', $medecinIdFromToken);
            }
            
            $prochainRdv = $query->orderBy('OrdreRDV', 'asc')->first();
            


            // Récupérer les rendez-vous du médecin pour la journée
            $rendezVousMedecinJournee = collect();
            $estAujourdhui = false;
            
            // Gestion des contraintes temporelles
            $dateAujourdhui = now()->format('Y-m-d');
            $estAujourdhui = false;
            $estFutur = false;
            $estPasse = false;
            $messageContrainte = null;
            
            if ($prochainRdv) {
                // Comparer les dates - s'assurer que les deux sont au même format
                $dateRdv = $prochainRdv->dtPrevuRDV;
                $dateRdvFormatted = is_string($dateRdv) ? $dateRdv : $dateRdv->format('Y-m-d');
                
                // Debug pour voir les valeurs
                \Log::info('Date RDV: ' . $dateRdvFormatted);
                \Log::info('Date Aujourd\'hui: ' . $dateAujourdhui);
                
                $estAujourdhui = ($dateRdvFormatted === $dateAujourdhui);
                $estFutur = ($dateRdvFormatted > $dateAujourdhui);
                $estPasse = ($dateRdvFormatted < $dateAujourdhui);
                
                // Debug pour voir les résultats
                \Log::info('Est Aujourd\'hui: ' . ($estAujourdhui ? 'true' : 'false'));
                \Log::info('Est Futur: ' . ($estFutur ? 'true' : 'false'));
                \Log::info('Est Passé: ' . ($estPasse ? 'true' : 'false'));
                
                // Définir les messages selon la date
                if ($estFutur) {
                    $messageContrainte = 'Attendre le jour de votre RDV';
                } elseif ($estPasse) {
                    $messageContrainte = 'Votre RDV a dépassé et le lien est expiré';
                }
                
                // Afficher la file d'attente seulement si le rendez-vous est aujourd'hui
                if ($estAujourdhui) {
                    // Récupérer TOUS les rendez-vous de la journée pour le médecin (y compris terminés)
                    $rendezVousMedecinJournee = Rendezvou::with(['patient', 'medecin'])
                        ->where('fkidMedecin', $prochainRdv->fkidMedecin)
                        ->whereDate('dtPrevuRDV', $dateToken)
                        ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
                        ->orderBy('OrdreRDV', 'asc')
                        ->get();
                }
            }

            $fileAttente = null;
            $positionPatient = null;
            $tempsAttenteEstime = null;
            $patientEnCours = null;
            $positionPatientEnCours = null;
            $patientsAvantMoi = 0;

            if ($prochainRdv && $estAujourdhui) {
                // Utiliser directement l'ordreRDV du patient comme position
                $positionPatient = $prochainRdv->OrdreRDV;
                
                // Calculer le nombre de patients avant ce patient dans la file d'attente
                // Utiliser $rendezVousMedecinJournee qui contient tous les RDV (sauf annulés)
                $patientsAvantMoi = $rendezVousMedecinJournee->filter(function($rdv) use ($prochainRdv) {
                    return $rdv->OrdreRDV < $prochainRdv->OrdreRDV && 
                           !in_array($rdv->rdvConfirmer, ['Terminé', 'terminé']);
                })->count();
                
                // Estimer le temps d'attente basé sur les patients réellement en attente
                $tempsAttenteEstime = $patientsAvantMoi * 15; // 15 minutes par patient en moyenne

                // Trouver le patient en cours de traitement
                $patientEnCours = $rendezVousMedecinJournee->first(function($rdv) {
                    return $rdv->rdvConfirmer == 'En cours';
                });

                if ($patientEnCours) {
                    $positionPatientEnCours = $patientEnCours->OrdreRDV;
                }
                
                // $fileAttente est maintenant identique à $rendezVousMedecinJournee pour la cohérence
                $fileAttente = $rendezVousMedecinJournee;
            }

            return view('patient.rendez-vous', compact(
                'patient', 
                'rendezVous', 
                'rendezVousMedecinJournee',
                'prochainRdv', 
                'fileAttente', 
                'positionPatient', 
                'tempsAttenteEstime',
                'patientEnCours',
                'positionPatientEnCours',
                'patientsAvantMoi',
                'estAujourdhui',
                'estFutur',
                'estPasse',
                'messageContrainte'
            ));
            
        } catch (\Exception $e) {
            return view('patient.error', ['message' => 'Erreur lors du chargement des données']);
        }
    }

    /**
     * Affiche les consultations d'un patient via token
     */
    public function showConsultation($token)
    {
        try {
            // Décoder le token pour obtenir l'ID du patient
            $patientId = $this->decodeToken($token);
            
            if (!$patientId) {
                return view('patient.error', ['message' => 'Token invalide ou expiré']);
            }

            $patient = Patient::find($patientId);
            
            if (!$patient) {
                return view('patient.error', ['message' => 'Patient non trouvé']);
            }

            // Récupérer toutes les consultations du patient
            $consultations = Consultation::with(['medecin', 'cabinet'])
                ->where('fkidPatient', $patientId)
                ->orderBy('DateConsultation', 'desc')
                ->get();

            return view('patient.consultation', compact('patient', 'consultations'));
            
        } catch (\Exception $e) {
            return view('patient.error', ['message' => 'Erreur lors du chargement des données']);
        }
    }

    /**
     * Génère un token signé pour un patient (HMAC-SHA256)
     * Format interne : patientId|date|medecinId
     * Format token   : base64url(payload).base64url(signature)
     *
     * @param int $patientId ID du patient
     * @param string|null $dateRendezVous Date du rendez-vous (Y-m-d), si null utilise aujourd'hui
     * @param int|null $medecinId ID du médecin (optionnel pour compatibilité)
     */
    public static function generateToken($patientId, $dateRendezVous = null, $medecinId = null)
    {
        $dateToken = $dateRendezVous ? date('Y-m-d', strtotime($dateRendezVous)) : date('Y-m-d');
        $payload   = $medecinId
            ? ($patientId . '|' . $dateToken . '|' . $medecinId)
            : ($patientId . '|' . $dateToken);

        $encodedPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature      = hash_hmac('sha256', $encodedPayload, config('app.key'));
        $encodedSig     = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $encodedPayload . '.' . $encodedSig;
    }

    /**
     * Décode le payload d'un token (nouveau format signé ou ancien format base64 brut).
     * Retourne le tableau des parties [patientId, date, medecinId?] ou null si invalide.
     */
    private function decodeTokenParts($token)
    {
        // Nouveau format signé : <payload>.<signature>
        if (substr_count($token, '.') === 1) {
            [$encodedPayload, $encodedSig] = explode('.', $token, 2);

            $expectedSig = hash_hmac('sha256', $encodedPayload, config('app.key'));
            $expectedEnc = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

            if (!hash_equals($expectedEnc, $encodedSig)) {
                return null; // Signature invalide
            }

            $payload = base64_decode(strtr($encodedPayload, '-_', '+/'));
            $parts   = explode('|', $payload);
            return count($parts) >= 2 ? $parts : null;
        }

        // Ancien format brut base64 (rétrocompatibilité — sans vérification de signature)
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return null;
        }
        $parts = explode('|', $decoded);
        return count($parts) >= 2 ? $parts : null;
    }

    /**
     * Extrait la date du token
     */
    private function getDateFromToken($token)
    {
        $parts = $this->decodeTokenParts($token);
        return ($parts && isset($parts[1])) ? $parts[1] : date('Y-m-d');
    }

    /**
     * Extrait l'ID du médecin du token
     */
    private function getMedecinIdFromToken($token)
    {
        $parts = $this->decodeTokenParts($token);
        return ($parts && isset($parts[2])) ? $parts[2] : null;
    }

    /**
     * Décode un token pour obtenir l'ID du patient
     */
    private function decodeToken($token)
    {
        try {
            $parts = $this->decodeTokenParts($token);
            if ($parts) {
                return $parts[0];
            }

            // Dernier recours : ancien token contenant seulement une date (format legacy)
            $dateToken  = base64_decode($token, true);
            $dateDuJour = date('Y-m-d');
            if ($dateToken === $dateDuJour) {
                $rdv = \App\Models\Rendezvou::whereDate('dtPrevuRDV', $dateDuJour)->first();
                return $rdv ? $rdv->fkidPatient : null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
} 