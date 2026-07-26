<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-push {patient_id? : ID du patient à notifier (par défaut : tous les patients abonnés)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie une notification Web Push de test à un patient (ou à tous les patients abonnés)';

    /**
     * Execute the console command.
     */
    public function handle(WebPushService $webPush)
    {
        $patientId = $this->argument('patient_id');

        $patientIds = $patientId
            ? [(int) $patientId]
            : PushSubscription::query()->distinct()->pluck('patient_id')->all();

        if (empty($patientIds)) {
            $this->error('Aucun patient abonné aux notifications push.');
            return self::FAILURE;
        }

        foreach ($patientIds as $id) {
            $abonnements = PushSubscription::where('patient_id', $id)->count();
            if ($abonnements === 0) {
                $this->warn("Patient #$id : aucun abonnement push trouvé.");
                continue;
            }

            $webPush->envoyerAuPatient(
                $id,
                'Notification de test',
                'Ceci est un test d\'envoi de notification Web Push.',
                '/espace-patient'
            );

            $this->info("Patient #$id : notification envoyée à $abonnements abonnement(s).");
        }

        return self::SUCCESS;
    }
}
