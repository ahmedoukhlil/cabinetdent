<div class="space-y-5">
    @if ($user->estDentiste())
        <livewire:ma-journee />
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Bonjour {{ $user->name }}</h1>
                <p class="text-sm text-slate-600 first-letter:uppercase">{{ now()->isoFormat('dddd D MMMM') }}</p>
            </div>
            @if ($consultationsPossibles)
                <button type="button" x-on:click="$dispatch('consultation-sans-rdv')" class="btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i> Consultation sans RDV</button>
            @endif
        </div>
        <livewire:consultations-non-facturees />
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ([['planifie', 'À venir', 'ti-calendar-time'], ['arrive', 'En attente', 'ti-armchair'], ['en_cours', 'En soins', 'ti-dental'], ['termine', 'Terminés', 'ti-check']] as [$statut, $libelle, $icone])
                <a href="{{ route($statut === 'planifie' ? 'agenda' : 'salle-attente') }}" wire:navigate class="card hover:border-primary">
                    <p class="text-sm text-slate-600"><i class="ti {{ $icone }} mr-1" aria-hidden="true"></i>{{ $libelle }}</p>
                    <p class="text-xl font-semibold tabular-nums sm:text-2xl">{{ $jour[$statut] ?? 0 }}</p>
                </a>
            @endforeach
        </div>
    @endif

    @can('patient.view')
        <section class="card" aria-labelledby="recherche-titre">
            <h2 id="recherche-titre" class="mb-2 text-sm font-medium text-slate-700">Rechercher un patient</h2>
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <i class="ti ti-search absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="Nom, téléphone ou numéro de fiche" class="field pl-9" aria-label="Rechercher un patient">
                </div>
                @can('patient.create')
                    <a href="{{ route('patients', ['nouveau' => 1]) }}" wire:navigate class="btn-secondary"><i class="ti ti-user-plus" aria-hidden="true"></i>Nouveau patient</a>
                @endcan
            </div>
            @if ($resultats->isNotEmpty())
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($resultats as $patient)
                        <li class="flex items-center gap-3 py-2 text-sm">
                            <span class="badge bg-slate-100 text-slate-600">N° {{ $patient->numero_fiche }}</span>
                            <span class="flex-1">{{ $patient->nom_complet }}</span>
                            <span class="text-slate-500">{{ $patient->telephone }}</span>
                            <a href="{{ route('patients', ['fiche' => $patient->id]) }}" wire:navigate class="text-primary hover:underline">Ouvrir</a>
                            @can('rendez-vous.create')
                                <a href="{{ route('agenda', ['patient' => $patient->id]) }}" wire:navigate class="text-primary hover:underline">RDV</a>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @elseif (mb_strlen(trim($recherche)) >= 2)
                <p class="mt-3 text-sm text-slate-600">Aucun patient trouvé.</p>
            @endif
        </section>
    @endcan
</div>
