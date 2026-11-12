<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Plateforme' }} · Administration Sysdental</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
            <span class="flex min-w-0 items-center gap-2 font-semibold"><i class="ti ti-dental text-2xl" aria-hidden="true"></i> Sysdental <span class="rounded bg-white/15 px-2 py-0.5 text-xs font-medium">Plateforme</span></span>
            {{-- Mobile : la navigation passe sur sa propre ligne, pleine largeur ; à partir de sm elle reste à droite du titre. --}}
            <nav class="order-last grid w-full grid-cols-2 gap-1 sm:order-none sm:ml-auto sm:flex sm:w-auto" aria-label="Navigation plateforme">
                @foreach ([['plateforme.cabinets', 'Cabinets', 'ti-building-hospital'], ['plateforme.plans', 'Formules', 'ti-list-details']] as [$route, $libelle, $icone])
                    <a href="{{ route($route) }}" wire:navigate @if (request()->routeIs($route)) aria-current="page" @endif @class(['flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm whitespace-nowrap', 'bg-white/15 font-medium' => request()->routeIs($route), 'text-slate-300 hover:bg-white/10' => !request()->routeIs($route)])><i class="ti {{ $icone }}" aria-hidden="true"></i>{{ $libelle }}</a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('plateforme.logout') }}" class="ml-auto sm:ml-0">
                @csrf
                <button class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/10" title="{{ auth('plateforme')->user()?->email }}" aria-label="Déconnexion"><i class="ti ti-logout text-lg" aria-hidden="true"></i><span class="hidden sm:inline">Déconnexion</span></button>
            </form>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6">{{ $slot }}</main>
    <div x-data="{ messages: [] }" x-on:toast.window="messages.push($event.detail); setTimeout(() => messages.shift(), 4000)" class="fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4" aria-live="polite">
        <template x-for="(m, i) in messages" :key="i">
            <div class="rounded-lg px-4 py-2 text-sm text-white shadow-lg" :class="m.type === 'error' ? 'bg-red-700' : 'bg-slate-900'" x-text="m.message"></div>
        </template>
    </div>
    @livewireScripts
</body>
</html>
