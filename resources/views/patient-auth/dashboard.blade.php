<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace patient — SysMedical</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    @php $patient = auth('patient')->user(); @endphp
    <div class="max-w-lg mx-auto p-4">
        <div class="flex items-center justify-between mb-6 pt-4">
            <div>
                <h1 class="text-lg font-bold text-gray-800">
                    Bonjour {{ $patient->NomContact ?? trim(($patient->Prenom ?? '').' '.($patient->Nom ?? '')) }}
                </h1>
                <p class="text-sm text-gray-500">Bienvenue dans votre espace patient</p>
            </div>
            <form method="POST" action="{{ route('patient.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:underline">Déconnexion</button>
            </form>
        </div>

        @if(session('message'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('message') }}
        </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="text-2xl mb-1">🦷</div>
                <div class="font-medium text-sm">Plan de traitement</div>
                <div class="text-xs text-gray-400">Bientôt disponible</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="text-2xl mb-1">💳</div>
                <div class="font-medium text-sm">Paiements</div>
                <div class="text-xs text-gray-400">Bientôt disponible</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="text-2xl mb-1">🧾</div>
                <div class="font-medium text-sm">Factures</div>
                <div class="text-xs text-gray-400">Bientôt disponible</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="text-2xl mb-1">⏱️</div>
                <div class="font-medium text-sm">File d'attente</div>
                <div class="text-xs text-gray-400">Bientôt disponible</div>
            </div>
        </div>
    </div>
</body>
</html>
