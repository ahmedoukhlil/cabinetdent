<?php

namespace App\Services;

use App\Models\Rendezvou;
use Illuminate\Support\Carbon;

class FileAttenteService
{
    // Durée moyenne estimée par patient en attente (minutes) — même valeur
    // que celle utilisée historiquement dans PatientInterfaceController.
    const MINUTES_PAR_PATIENT = 15;

    /**
     * Calcule l'état de la file d'attente pour le rendez-vous du jour d'un
     * patient donné (ou null si le patient n'a pas de RDV aujourd'hui).
     *
     * Retourne un tableau avec : rdv, fileAttente, positionPatient,
     * patientsAvantMoi, tempsAttenteEstime, patientEnCours,
     * positionPatientEnCours — ou null si aucun RDV aujourd'hui.
     */
    public function pourPatientAujourdhui(int $patientId): ?array
    {
        $dateAujourdhui = now()->format('Y-m-d');

        $rdv = Rendezvou::with(['medecin', 'cabinet'])
            ->where('fkidPatient', $patientId)
            ->whereDate('dtPrevuRDV', $dateAujourdhui)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->orderBy('OrdreRDV', 'asc')
            ->first();

        if (!$rdv) {
            return null;
        }

        $fileAttente = Rendezvou::with(['patient', 'medecin'])
            ->where('fkidMedecin', $rdv->fkidMedecin)
            ->whereDate('dtPrevuRDV', $dateAujourdhui)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->orderBy('OrdreRDV', 'asc')
            ->get();

        $patientsAvantMoi = $fileAttente->filter(function ($r) use ($rdv) {
            return $r->OrdreRDV < $rdv->OrdreRDV
                && !in_array($r->rdvConfirmer, ['Terminé', 'terminé']);
        })->count();

        $patientEnCours = $fileAttente->first(fn ($r) => $r->rdvConfirmer === 'En cours');

        return [
            'rdv' => $rdv,
            'fileAttente' => $fileAttente,
            'positionPatient' => $rdv->OrdreRDV,
            'patientsAvantMoi' => $patientsAvantMoi,
            'tempsAttenteEstime' => $patientsAvantMoi * self::MINUTES_PAR_PATIENT,
            'patientEnCours' => $patientEnCours,
            'positionPatientEnCours' => $patientEnCours?->OrdreRDV,
        ];
    }
}
