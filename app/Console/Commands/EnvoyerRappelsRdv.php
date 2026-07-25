<?php

namespace App\Console\Commands;

use App\Models\Rendezvou;
use App\Services\WebPushService;
use Illuminate\Console\Command;

class EnvoyerRappelsRdv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:envoyer-rappels-rdv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie une notification Web Push aux patients ayant un rendez-vous demain, s\'ils sont abonnés';

    /**
     * Execute the console command.
     */
    public function handle(WebPushService $webPush)
    {
        $demain = now()->addDay()->format('Y-m-d');

        $rdvs = Rendezvou::with(['patient', 'medecin'])
            ->whereDate('dtPrevuRDV', $demain)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->whereHas('patient.pushSubscriptions')
            ->get();

        $envoyes = 0;
        foreach ($rdvs as $rdv) {
            if (!$rdv->patient) {
                continue;
            }

            $heure = $rdv->HeureRdv ? \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') : '';
            $medecin = $rdv->medecin ? 'Dr. ' . $rdv->medecin->Nom : '';

            $webPush->envoyerAuPatient(
                $rdv->patient->ID,
                'Rappel de rendez-vous',
                "Vous avez un rendez-vous demain" . ($heure ? " à $heure" : '') . ($medecin ? " avec $medecin" : '') . '.',
                '/espace-patient/file-attente'
            );
            $envoyes++;
        }

        $this->info("$envoyes rappel(s) envoyé(s) pour les rendez-vous du $demain.");
    }
}
