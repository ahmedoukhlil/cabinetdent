<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace patient — SysMedical</title>
    @include('patient-auth.partials.pwa-head')
    @include('patient-auth.partials.tailwind-config')
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Créez votre mot de passe</h1>
            <p class="text-sm text-gray-500">Pour vos prochaines connexions, un mot de passe sera nécessaire</p>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('patient.definir-mot-de-passe.save') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="6" autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" required minlength="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-dark">
                Créer mon mot de passe
            </button>
        </form>
    </div>
</body>
</html>
