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
            <nav class="order-last grid w-full grid-cols-3 gap-1 sm:order-none sm:ml-auto sm:flex sm:w-auto" aria-label="Navigation plateforme">
                @foreach ([['plateforme.cabinets', 'Cabinets', 'ti-building-hospital'], ['plateforme.plans', 'Formules', 'ti-list-details'], ['plateforme.administrateurs', 'Admins', 'ti-shield-lock']] as [$route, $libelle, $icone])
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
    <div x-data="{ messages: [], suivant: 0,
                   ajouter(d) { const id = ++this.suivant; this.messages.push({ ...d, id }); setTimeout(() => this.retirer(id), 4000) },
                   retirer(id) { this.messages = this.messages.filter((m) => m.id !== id) } }"
         x-on:toast.window="ajouter($event.detail)"
         class="pointer-events-none fixed top-[calc(env(safe-area-inset-top)+1rem)] right-4 left-4 z-[70] flex flex-col items-end gap-2 sm:left-auto sm:w-96" aria-live="polite">
        <template x-for="m in messages" :key="m.id">
            <button type="button" x-on:click="retirer(m.id)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-4 opacity-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
                    class="pointer-events-auto max-w-full rounded-lg px-4 py-3 text-left text-sm text-white shadow-lg" :class="m.type === 'error' ? 'bg-red-600' : 'bg-emerald-600'" x-text="m.message" title="Fermer"></button>
        </template>
    </div>
    @include('partials.fenetre-confirmation')
    @livewireScripts
</body>
</html>
