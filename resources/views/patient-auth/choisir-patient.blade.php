<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace patient — SysMedical</title>
    @include('patient-auth.partials.pwa-head')
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Qui êtes-vous ?</h1>
            <p class="text-sm text-gray-500">Plusieurs profils utilisent ce numéro</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
            @foreach($patients as $patient)
            <form method="POST" action="{{ route('patient.login.choisir') }}">
                @csrf
                <input type="hidden" name="telephone" value="{{ $telephone }}">
                <input type="hidden" name="patient_id" value="{{ $patient->ID }}">
                <button type="submit" class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center justify-between">
                    <span class="font-medium text-gray-800">
                        {{ $patient->NomContact ?? trim(($patient->Prenom ?? '').' '.($patient->Nom ?? '')) }}
                    </span>
                    <i class="text-gray-400">&rsaquo;</i>
                </button>
            </form>
            @endforeach
        </div>

        <a href="{{ route('patient.login') }}" class="block text-center text-sm text-blue-600 mt-4 hover:underline">
            Ce n'est pas moi, revenir en arrière
        </a>
    </div>
</body>
</html>
