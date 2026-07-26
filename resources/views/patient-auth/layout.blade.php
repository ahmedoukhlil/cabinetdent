<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Espace patient') — SysMedical</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('patient-auth.partials.pwa-head')
    @include('patient-auth.partials.tailwind-config')
</head>
<body class="bg-gray-50 min-h-screen pb-20">
    <div class="max-w-lg mx-auto p-4">
        <div class="flex items-center justify-between mb-4 pt-2">
            <a href="{{ route('patient.dashboard') }}" class="flex items-center gap-2 text-gray-800">
                <i class="fas fa-tooth text-xl text-primary"></i>
                <span class="font-bold">SysMedical</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('patient.notifications') }}" class="relative text-gray-600 {{ request()->routeIs('patient.notifications') ? 'text-primary' : '' }}">
                    <i class="fas fa-bell text-lg"></i>
                    @php $nbNonLues = \App\Models\PatientNotification::where('patient_id', auth('patient')->id())->whereNull('lu_le')->count(); @endphp
                    @if($nbNonLues > 0)
                    <span class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[10px] leading-none rounded-full w-4 h-4 flex items-center justify-center">
                        {{ $nbNonLues > 9 ? '9+' : $nbNonLues }}
                    </span>
                    @endif
                </a>
                <form method="POST" action="{{ route('patient.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:underline">Déconnexion</button>
                </form>
            </div>
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
        <a href="{{ route('patient.dashboard') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.dashboard') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-house text-lg"></i> Accueil
        </a>
        <a href="{{ route('patient.plan-traitement') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.plan-traitement') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-tooth text-lg"></i> Traitement
        </a>
        <a href="{{ route('patient.factures') }}" class="flex flex-col items-center text-xs px-3 py-1 {{ request()->routeIs('patient.factures*') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-file-invoice text-lg"></i> Factures
        </a>
        <a href="{{ route('patient.paiements') }}" class="flex flex-col items-center text-xs px-2 py-1 {{ request()->routeIs('patient.paiements') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-credit-card text-lg"></i> Paiements
        </a>
        <a href="{{ route('patient.file-attente') }}" class="flex flex-col items-center text-xs px-2 py-1 {{ request()->routeIs('patient.file-attente') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-clock text-lg"></i> Attente
        </a>
    </nav>
</body>
</html>
