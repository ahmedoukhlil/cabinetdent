<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Espace patient') — SysMedical</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen pb-20">
    <div class="max-w-lg mx-auto p-4">
        <div class="flex items-center justify-between mb-4 pt-2">
            <a href="{{ route('patient.dashboard') }}" class="flex items-center gap-2 text-gray-800">
                <span class="text-xl">🦷</span>
                <span class="font-bold">SysMedical</span>
            </a>
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

        @yield('content')
    </div>

    {{-- Navigation basse, style app mobile --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex justify-around py-2 max-w-lg mx-auto">
        <a href="{{ route('patient.dashboard') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
            <span class="text-lg">🏠</span> Accueil
        </a>
        <a href="{{ route('patient.plan-traitement') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.plan-traitement') ? 'text-blue-600' : 'text-gray-500' }}">
            <span class="text-lg">🦷</span> Traitement
        </a>
        <a href="{{ route('patient.factures') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.factures*') ? 'text-blue-600' : 'text-gray-500' }}">
            <span class="text-lg">🧾</span> Factures
        </a>
        <a href="{{ route('patient.paiements') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.paiements') ? 'text-blue-600' : 'text-gray-500' }}">
            <span class="text-lg">💳</span> Paiements
        </a>
    </nav>
</body>
</html>
