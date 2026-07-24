<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Rendezvou extends Model
{
    // Nom de la table
    protected $table = 'rendezvous';

    // Clé primaire
    protected $primaryKey = 'IDRdv';

    // Pas de timestamps Laravel (created_at, updated_at)
    public $timestamps = false;

    // Champs à caster en date/heure
    protected $dates = [
        'DtAjRdv',
        'dtPrevuRDV',
        'HeureRdv',
        'HeureConfRDV'
    ];

    // Champs assignables en masse
    protected $fillable = [
        'ActePrevu',
        'DtAjRdv',
        'dtPrevuRDV',
        'user',
        'HeureRdv',
        'fkidPatient',
        'rdvConfirmer',
        'debut_en_cours',
        'fkidMedecin',
        'OrdreRDV',
        'HeureConfRDV',
        'fkidcabinet',
        'fkidFacture'
    ];

    /**
     * Relation : le médecin associé à ce rendez-vous
     */
    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidMedecin', 'idMedecin');
    }

    /**
     * Relation : le patient associé à ce rendez-vous
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    /**
     * Relation : le cabinet associé à ce rendez-vous
     */
    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    /**
     * Génère le prochain numéro d'ordre pour la date et le médecin donnés
     * Utilise un verrou pour éviter les conditions de course
     */
    public static function generateNextOrderNumber($date, $medecinId = null)
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        // Utiliser une transaction avec verrou pour éviter les conditions de course
        return \DB::transaction(function () use ($date, $medecinId) {
            $query = self::select('OrdreRDV')
                ->whereDate('dtPrevuRDV', $date->format('Y-m-d'))
                ->where('fkidcabinet', Auth::user()->fkidcabinet);
                
            // Si un médecin est spécifié, filtrer par médecin
            if ($medecinId) {
                $query->where('fkidMedecin', $medecinId);
            }
            
            // Utiliser lockForUpdate pour éviter les conditions de course
            $lastOrder = $query->lockForUpdate()->max('OrdreRDV');

            return ($lastOrder ?? 0) + 1;
        });
    }

    /**
     * Met à jour le statut d'un rendez-vous et gère automatiquement les conflits
     * @param int $rdvId ID du rendez-vous
     * @param string $nouveauStatut Nouveau statut
     * @return array ['success' => bool, 'message' => string]
     */
    public static function updateStatusWithConflictManagement($rdvId, $nouveauStatut)
    {
        try {
            $rdv = self::find($rdvId);
            
            if (!$rdv) {
                return ['success' => false, 'message' => 'Rendez-vous non trouvé.'];
            }

            // Si le nouveau statut est "En cours", terminer automatiquement les autres patients
            if ($nouveauStatut === 'En cours') {
                // Terminer tous les autres patients "En cours" du même médecin pour la même journée
                $patientsTermines = self::where('fkidMedecin', $rdv->fkidMedecin)
                    ->whereDate('dtPrevuRDV', $rdv->dtPrevuRDV)
                    ->where('IDRdv', '!=', $rdv->IDRdv)
                    ->where('rdvConfirmer', 'En cours')
                    ->update(['rdvConfirmer' => 'Terminé']);
                
                $message = 'Statut mis à jour.';
                if ($patientsTermines > 0) {
                    $message .= " {$patientsTermines} autre(s) patient(s) en cours ont été automatiquement terminé(s).";
                }
            } else {
                $message = 'Statut du rendez-vous mis à jour avec succès.';
            }

            $updateData = ['rdvConfirmer' => $nouveauStatut];
            
            // Si on confirme, ajouter l'heure de confirmation
            if ($nouveauStatut === 'Confirmé') {
                $updateData['HeureConfRDV'] = now();
            }
            
            $rdv->update($updateData);
            
            return ['success' => true, 'message' => $message];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage()];
        }
    }

    /**
     * Vérifie s'il y a un conflit d'horaire pour un médecin à une date/heure donnée
     * @param int $medecinId ID du médecin
     * @param string $date Date du rendez-vous (Y-m-d)
     * @param string $heure Heure du rendez-vous (H:i)
     * @param int|null $excludeRdvId ID du rendez-vous à exclure (pour les modifications)
     * @return bool True s'il y a un conflit
     */
    public static function hasConflict($medecinId, $date, $heure, $excludeRdvId = null)
    {
        // Convertir la date et l'heure en datetime
        $dateTimeRdv = Carbon::parse($date . ' ' . $heure);
        
        // Définir la durée d'un rendez-vous (10 minutes)
        $dureeRdv = 10; // minutes
        
        // Calculer l'heure de fin du rendez-vous
        $heureFin = $dateTimeRdv->copy()->addMinutes($dureeRdv);
        
        // Chercher les rendez-vous existants pour ce médecin à cette date
        $query = self::where('fkidMedecin', $medecinId)
            ->whereDate('dtPrevuRDV', $date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet);
        
        // Exclure le rendez-vous en cours de modification
        if ($excludeRdvId) {
            $query->where('IDRdv', '!=', $excludeRdvId);
        }
        
        $rendezVousExistants = $query->get();
        
        foreach ($rendezVousExistants as $rdv) {
            if (!$rdv->HeureRdv) continue;
            
            $heureDebutExistant = Carbon::parse($rdv->HeureRdv);
            $heureFinExistant = $heureDebutExistant->copy()->addMinutes($dureeRdv);
            
            // Vérifier s'il y a un chevauchement
            if ($dateTimeRdv < $heureFinExistant && $heureFin > $heureDebutExistant) {
                return true; // Conflit détecté
            }
        }
        
        return false; // Aucun conflit
    }

    /**
     * Récupère les créneaux disponibles pour un médecin à une date donnée
     * @param int $medecinId ID du médecin
     * @param string $date Date (Y-m-d)
     * @return array Liste des créneaux disponibles
     */
    public static function getCreneauxDisponibles($medecinId, $date)
    {
        // Heures de travail (8h-18h)
        $heureDebut = 8;
        $heureFin = 18;
        $dureeCreneau = 10; // minutes (créneaux de 10 minutes)
        
        $creneauxDisponibles = [];
        
        for ($heure = $heureDebut; $heure < $heureFin; $heure++) {
            for ($minute = 0; $minute < 60; $minute += $dureeCreneau) {
                $heureCreneau = sprintf('%02d:%02d', $heure, $minute);
                
                // Vérifier s'il y a un conflit pour ce créneau
                if (!self::hasConflict($medecinId, $date, $heureCreneau)) {
                    $creneauxDisponibles[] = $heureCreneau;
                }
            }
        }
        
        return $creneauxDisponibles;
    }

    /**
     * Propose le prochain créneau disponible après le dernier rendez-vous
     * @param int $medecinId ID du médecin
     * @param string $date Date (Y-m-d)
     * @return string|null Heure proposée ou null si aucun créneau disponible
     */
    public static function getProchainCreneauPropose($medecinId, $date)
    {
        // Récupérer le dernier rendez-vous du médecin pour cette date
        $dernierRdv = self::where('fkidMedecin', $medecinId)
            ->whereDate('dtPrevuRDV', $date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->orderBy('HeureRdv', 'desc')
            ->first();

        if (!$dernierRdv || !$dernierRdv->HeureRdv) {
            // Aucun RDV existant, proposer 8h00
            return '08:00';
        }

        // Calculer le prochain créneau (10 minutes après la fin du dernier RDV)
        $heureFinDernierRdv = Carbon::parse($dernierRdv->HeureRdv)->addMinutes(10);
        
        // Vérifier que l'heure proposée est dans les heures de travail
        if ($heureFinDernierRdv->hour >= 18) {
            return null; // Plus de créneaux disponibles aujourd'hui
        }

        return $heureFinDernierRdv->format('H:i');
    }
} 