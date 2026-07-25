@extends('patient-auth.layout')

@section('title', 'Accueil')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-1">
    Bonjour {{ $patient->NomContact ?? trim(($patient->Prenom ?? '').' '.($patient->Nom ?? '')) }}
</h1>
<p class="text-sm text-gray-500 mb-6">Bienvenue dans votre espace patient</p>

<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('patient.plan-traitement') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">🦷</div>
        <div class="font-medium text-sm">Plan de traitement</div>
        <div class="text-xs text-gray-500">{{ $planEnCours }} en cours</div>
    </a>
    <a href="{{ route('patient.factures') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">🧾</div>
        <div class="font-medium text-sm">Factures</div>
        <div class="text-xs {{ $facturesImpayees > 0 ? 'text-orange-600' : 'text-gray-500' }}">
            {{ $facturesImpayees > 0 ? $facturesImpayees . ' impayée(s)' : 'À jour' }}
        </div>
    </a>
    <a href="{{ route('patient.paiements') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">💳</div>
        <div class="font-medium text-sm">Paiements</div>
        <div class="text-xs text-gray-500">Historique</div>
    </a>
    <a href="{{ route('patient.file-attente') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">⏱️</div>
        <div class="font-medium text-sm">File d'attente</div>
        <div class="text-xs text-gray-500">Voir en direct</div>
    </a>
</div>

<div id="notif-bloc" class="mt-4 bg-white p-4 rounded-xl border border-gray-100 shadow-sm hidden">
    <div class="flex items-center justify-between">
        <div>
            <div class="font-medium text-sm">🔔 Rappels de rendez-vous</div>
            <div class="text-xs text-gray-500" id="notif-statut">Recevez une notification avant vos RDV</div>
        </div>
        <button id="notif-bouton" type="button" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700">
            Activer
        </button>
    </div>
</div>

<script>
(function () {
    const VAPID_PUBLIC_KEY = @json(config('services.vapid.public_key'));

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !VAPID_PUBLIC_KEY) {
        return;
    }

    const bloc = document.getElementById('notif-bloc');
    const bouton = document.getElementById('notif-bouton');
    const statut = document.getElementById('notif-statut');
    bloc.classList.remove('hidden');

    function majAffichage(estAbonne) {
        if (estAbonne) {
            statut.textContent = 'Notifications activées';
            bouton.textContent = 'Désactiver';
        } else {
            statut.textContent = 'Recevez une notification avant vos RDV';
            bouton.textContent = 'Activer';
        }
    }

    navigator.serviceWorker.ready.then((registration) => {
        registration.pushManager.getSubscription().then((sub) => majAffichage(!!sub));

        bouton.addEventListener('click', async () => {
            const existante = await registration.pushManager.getSubscription();

            if (existante) {
                await fetch('{{ route('patient.push.unsubscribe') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ endpoint: existante.endpoint }),
                });
                await existante.unsubscribe();
                majAffichage(false);
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });

            await fetch('{{ route('patient.push.subscribe') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(subscription.toJSON()),
            });
            majAffichage(true);
        });
    });
})();
</script>
@endsection
