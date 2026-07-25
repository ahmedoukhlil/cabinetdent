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
            <h1 class="text-xl font-bold text-gray-800">Bonjour {{ $nom }}</h1>
            <p class="text-sm text-gray-500">Entrez votre mot de passe</p>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('patient.login.mot-de-passe') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $patient_id }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input type="password" name="password" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700">
                Se connecter
            </button>
        </form>

        <a href="{{ route('patient.login') }}" class="block text-center text-sm text-blue-600 mt-4 hover:underline">
            Utiliser un autre numéro
        </a>
    </div>
</body>
</html>
