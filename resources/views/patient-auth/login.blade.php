<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace patient — SysMedical</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('patient-auth.partials.pwa-head')
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Espace patient</h1>
            <p class="text-sm text-gray-500">Connectez-vous avec votre numéro de téléphone</p>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('patient.login.telephone') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de téléphone</label>
                <input type="tel" name="telephone" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="Ex: 38230000">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700">
                Continuer
            </button>
        </form>

        <p class="text-xs text-gray-400 text-center mt-4">
            Première connexion : votre numéro de téléphone suffit. Un mot de passe vous sera ensuite demandé de créer.
        </p>
    </div>
</body>
</html>
