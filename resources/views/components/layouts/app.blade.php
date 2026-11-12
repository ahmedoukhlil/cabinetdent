<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Accueil' }} · Sysdental</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased" x-data="{ menu: false, navigation: false }" x-on:livewire:navigating.window="navigation = true" x-on:livewire:navigated.window="navigation = false">
    <div x-show="navigation" x-cloak class="fixed inset-x-0 top-0 z-[80] h-1 overflow-hidden bg-primary-light" role="status" aria-label="Chargement de la page">
        <span class="block h-full w-1/3 animate-pulse rounded-full bg-primary"></span>
    </div>
    @php
        $user = auth()->user();
        // Navigation regroupée par usage : le quotidien, l'argent, le paramétrage.
        $groupes = [
            'Activité' => [
                ['accueil', 'Accueil', 'ti-home', null],
                ['agenda', 'Agenda', 'ti-calendar', 'rendez-vous.view'],
                ['salle-attente', "Salle d'attente", 'ti-armchair', 'salle-attente.view'],
                ['patients', 'Patients', 'ti-users', 'patient.view'],
            ],
            'Finances' => [
                ['factures', 'Facturation', 'ti-file-invoice', 'facture.view'],
                ['caisse', 'Caisse', 'ti-cash-register', 'caisse.view'],
                ['statistiques', 'Statistiques', 'ti-chart-bar', 'statistiques.view'],
            ],
            'Gestion' => [
                ['stock', 'Stock', 'ti-package', 'stock.manage'],
                ['tarifs', 'Tarifs et assurances', 'ti-receipt-2', 'tarifs.manage'],
                ['modes-paiement', 'Modes de paiement', 'ti-credit-card', 'tarifs.manage'],
                ['equipe', 'Équipe et cabinet', 'ti-users-group', 'user.manage'],
            ],
        ];
        $initiales = collect(explode(' ', $user->name))->map(fn ($m) => mb_substr($m, 0, 1))->take(2)->implode('');
    @endphp

    {{-- Barre du haut, mobile et tablette --}}
    <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
        <button type="button" x-on:click="menu = true" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" aria-label="Ouvrir le menu"><i class="ti ti-menu-2 text-xl" aria-hidden="true"></i></button>
        <a href="{{ route('accueil') }}" wire:navigate class="flex items-center gap-2 font-semibold text-primary"><i class="ti ti-dental text-2xl" aria-hidden="true"></i> Sysdental</a>
        <span class="ml-auto truncate text-sm text-slate-500">{{ $user->cabinet->nom }}</span>
    </header>

    {{-- Fond sombre du menu mobile --}}
    <div x-show="menu" x-cloak x-transition.opacity x-on:click="menu = false" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"></div>

    {{-- Barre latérale --}}
    <aside x-bind:class="menu ? 'translate-x-0' : '-translate-x-full'" x-on:keydown.escape.window="menu = false"
           class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary text-white"><i class="ti ti-dental text-2xl" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <a href="{{ route('accueil') }}" wire:navigate class="block font-semibold leading-tight text-primary">Sysdental</a>
                <p class="truncate text-xs text-slate-500" title="{{ $user->cabinet->nom }}">{{ $user->cabinet->nom }}</p>
            </div>
            <button type="button" x-on:click="menu = false" class="ml-auto rounded p-1 text-slate-400 hover:text-slate-700 lg:hidden" aria-label="Fermer le menu"><i class="ti ti-x" aria-hidden="true"></i></button>
        </div>

        <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4" aria-label="Navigation principale">
            @foreach ($groupes as $groupe => $liens)
                @php $visibles = array_filter($liens, fn ($l) => !$l[3] || $user->can($l[3])); @endphp
                @if ($visibles)
                    <div>
                        <p class="mb-1 px-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $groupe }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($visibles as [$route, $libelle, $icone])
                                @php $actif = request()->routeIs($route); @endphp
                                <li>
                                    <a href="{{ route($route) }}" wire:navigate @if ($actif) aria-current="page" @endif
                                       @class(['relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition', 'bg-primary/10 font-medium text-primary before:absolute before:inset-y-1.5 before:left-0 before:w-1 before:rounded-full before:bg-primary' => $actif, 'text-slate-700 hover:bg-slate-100' => !$actif])>
                                        <i class="ti {{ $icone }} text-lg" aria-hidden="true"></i>{{ $libelle }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-3 border-t border-slate-200 px-4 py-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700" aria-hidden="true">{{ mb_strtoupper($initiales) }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user->role->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-red-700" title="Déconnexion" aria-label="Déconnexion"><i class="ti ti-logout text-lg" aria-hidden="true"></i></button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-64">
        <main class="mx-auto max-w-6xl px-4 py-6 pb-24 md:px-6 lg:px-8 lg:pb-8">
            {{ $slot }}
            @include('partials.bandeau-abonnement')
        </main>
    </div>

    {{-- Barre basse mobile --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 grid grid-cols-5 border-t border-slate-200 bg-white md:hidden" aria-label="Navigation mobile">
        @foreach ([['accueil', 'Accueil', 'ti-home', null], ['agenda', 'Agenda', 'ti-calendar', 'rendez-vous.view'], ['salle-attente', 'Attente', 'ti-armchair', 'salle-attente.view'], ['patients', 'Patients', 'ti-users', 'patient.view'], ['factures', 'Factures', 'ti-file-invoice', 'facture.view']] as [$route, $libelle, $icone, $permission])
            @if (!$permission || $user->can($permission))
                <a href="{{ route($route) }}" wire:navigate @class(['flex flex-col items-center gap-0.5 py-2 text-xs', 'text-primary font-medium' => request()->routeIs($route), 'text-slate-600' => !request()->routeIs($route)])>
                    <i class="ti {{ $icone }} text-xl" aria-hidden="true"></i>{{ $libelle }}
                </a>
            @endif
        @endforeach
    </nav>

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
    {{-- Fenêtre de confirmation (remplace la boîte native de wire:confirm, voir resources/js/confirmation.js) --}}
    <div x-data="{ ouvert: false, message: '', libelle: '', danger: false, valider: null,
                   ouvrir(d) { Object.assign(this, d, { ouvert: true }); this.$nextTick(() => this.$refs.ok.focus()) },
                   confirmer() { this.ouvert = false; this.valider?.() } }"
         x-on:confirmer.window="ouvrir($event.detail)" x-on:keydown.escape.window="ouvert = false">
        <div x-show="ouvert" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/40 p-4 sm:items-center"
             x-on:click.self="ouvert = false" role="alertdialog" aria-modal="true" aria-labelledby="conf-titre" aria-describedby="conf-message">
            <div x-show="ouvert" x-transition class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                <div class="flex gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full" :class="danger ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary'">
                        <i class="ti text-xl" :class="danger ? 'ti-alert-triangle' : 'ti-help-circle'" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 id="conf-titre" class="font-semibold">Confirmation</h2>
                        <p id="conf-message" class="mt-1 text-sm text-slate-600" x-text="message"></p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="ouvert = false" class="btn-secondary">Non, revenir</button>
                    <button type="button" x-ref="ok" x-on:click="confirmer()" :class="danger ? 'btn bg-red-600 text-white hover:bg-red-700' : 'btn-primary'" x-text="libelle"></button>
                </div>
            </div>
        </div>
    </div>

    @auth
        <livewire:consultation-rapide />
    @endauth
    @livewireScripts
    @include('partials.pwa-bandeaux')
</body>
</html>
