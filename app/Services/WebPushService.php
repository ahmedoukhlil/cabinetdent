<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Envoie une notification à tous les abonnements d'un patient.
     * Supprime automatiquement les abonnements expirés/invalides (410/404)
     * renvoyés par le navigateur, pour ne pas les retenter indéfiniment.
     */
    public function envoyerAuPatient(int $patientId, string $titre, string $corps, ?string $url = null): void
    {
        $abonnements = PushSubscription::where('patient_id', $patientId)->get();

        foreach ($abonnements as $abonnement) {
            $subscription = Subscription::create([
                'endpoint' => $abonnement->endpoint,
                'publicKey' => $abonnement->public_key,
                'authToken' => $abonnement->auth_token,
                'contentEncoding' => $abonnement->content_encoding ?: 'aesgcm',
            ]);

            $this->webPush->queueNotification(
                $subscription,
                json_encode([
                    'title' => $titre,
                    'body' => $corps,
                    'url' => $url ?? '/espace-patient',
                ])
            );
        }

        foreach ($this->webPush->flush() as $rapport) {
            if (!$rapport->isSuccess() && in_array($rapport->getResponse()?->getStatusCode(), [404, 410], true)) {
                PushSubscription::where('endpoint', $rapport->getEndpoint())->delete();
            }
        }
    }
}
