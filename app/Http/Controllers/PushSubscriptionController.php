<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Enregistre (ou met à jour) l'abonnement Web Push du patient connecté.
     * Appelé par le JS après acceptation de la permission de notification.
     */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $patient = Auth::guard('patient')->user();

        PushSubscription::updateOrCreate(
            ['patient_id' => $patient->ID, 'endpoint' => $request->input('endpoint')],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => 'aesgcm',
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Supprime l'abonnement du patient (désactivation des notifications).
     */
    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        $patient = Auth::guard('patient')->user();

        PushSubscription::where('patient_id', $patient->ID)
            ->where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
