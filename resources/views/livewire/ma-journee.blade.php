<section wire:poll.60s class="space-y-3" aria-labelledby="ma-journee-titre">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 id="ma-journee-titre" class="text-lg font-semibold">Bonjour Dr {{ $user->name }}</h1>
            <p class="text-sm text-slate-600 first-letter:uppercase">{{ now()->isoFormat('dddd D MMMM') }} · {{ $nbRdv }} RDV aujourd'hui</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
        @if ($user->can('facture.edit') && \App\Models\Acte::consultations()->exists())
            <button type="button" x-on:click="$dispatch('consultation-sans-rdv')" class="btn-primary !py-2"><i class="ti ti-user-plus" aria-hidden="true"></i> Consultation sans RDV</button>
        @endif
        @if ($user->estProprietaire())
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1" role="group" aria-label="Portée des données">
                @foreach (['moi' => 'Mes données', 'cabinet' => 'Cabinet'] as $valeur => $libelle)
                    <button type="button" wire:click="setPortee('{{ $valeur }}')" aria-pressed="{{ $portee === $valeur ? 'true' : 'false' }}"
                        @class(['rounded-md px-3 py-1.5 text-sm', 'bg-primary text-white' => $portee === $valeur, 'text-slate-700 hover:bg-slate-50' => $portee !== $valeur])>{{ $libelle }}</button>
                @endforeach
            </div>
        @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
        <div @class(['card lg:col-span-3', '!border-2 !border-primary' => $enCours])>
            <p class="text-sm text-slate-600"><i class="ti ti-armchair-2 mr-1 text-primary" aria-hidden="true"></i>Patient au fauteuil</p>
            @if ($enCours)
                <p class="mt-1 text-xl font-semibold">{{ $enCours->patient->nom_complet }}</p>
                <p class="text-sm text-slate-600">
                    Depuis {{ $enCours->debut_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '—' }}
                    @if ($enCours->acte_prevu) · {{ $enCours->acte_prevu }} @endif
                    @if ($portee === 'cabinet') · Dr {{ $enCours->dentiste->name }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('dossier', $enCours->patient_id) }}" wire:navigate class="btn-primary"><i class="ti ti-dental" aria-hidden="true"></i>Dossier dentaire</a>
                    <a href="{{ route('patients', ['fiche' => $enCours->patient_id]) }}" wire:navigate class="btn-secondary"><i class="ti ti-file-text" aria-hidden="true"></i>Fiche</a>
                    @include('livewire.partials.puce-consultation', ['rdv' => $enCours])
                    <button type="button" wire:click="changerStatut({{ $enCours->id }}, 'termine')" wire:loading.attr="disabled" class="btn-secondary"><i class="ti ti-check" aria-hidden="true"></i>Terminer</button>
                </div>
            @else
                <p class="mt-2 text-sm text-slate-600">Aucun patient au fauteuil. Faites entrer le suivant depuis la salle d'attente.</p>
            @endif
        </div>

        <div class="card lg:col-span-2">
            <p class="text-sm text-slate-600"><i class="ti ti-users mr-1 text-primary" aria-hidden="true"></i>Salle d'attente · {{ $attente->count() }}</p>
            <ul class="mt-2 divide-y divide-slate-100">
                @forelse ($attente->take(4) as $rdv)
                    @php($minutes = (int) $rdv->arrive_at?->diffInMinutes(now()))
                    <li class="flex items-center gap-2 py-2 text-sm">
                        <span class="min-w-0 flex-1 truncate">
                            {{ $rdv->patient->nom_complet }}
                            @if ($portee === 'cabinet')<span class="text-slate-500"> · Dr {{ $rdv->dentiste->name }}</span>@endif
                        </span>
                        @include('livewire.partials.puce-consultation')
                        <span @class(['badge', 'bg-amber-100 text-amber-800' => $minutes >= 20, 'bg-slate-100 text-slate-600' => $minutes < 20])>{{ $minutes }} min</span>
                        @if (!$enCours && $rdv->dentiste_id === $user->id)
                            <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'en_cours')" class="rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary-light">Faire entrer</button>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-600">Personne en attente.</li>
                @endforelse
                @if ($prochain)
                    <li class="flex items-center gap-2 py-2 text-sm">
                        <span class="flex-1 text-slate-600">Prochain RDV</span>
                        <span>{{ $prochain->heure_courte }} · {{ $prochain->patient->nom_complet }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <livewire:consultations-non-facturees />

    @if ($synthese)
        <details class="rounded-xl border border-slate-200/80 bg-white shadow-sm">
            <summary class="flex cursor-pointer items-center gap-2 p-4 text-sm font-medium">
                <i class="ti ti-chart-bar text-primary" aria-hidden="true"></i>Synthèse du jour
                <span class="font-normal text-slate-500">· {{ $portee === 'cabinet' ? 'cabinet' : 'mes données' }}</span>
            </summary>
            <div class="grid grid-cols-2 gap-3 px-4 pb-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach (['Recette du jour' => number_format($synthese['recette'], 0, ',', ' ').' MRU', 'Impayés' => number_format($synthese['impayes'], 0, ',', ' ').' MRU', 'Patients vus' => $synthese['termines'], 'RDV restants' => $synthese['restants'], 'Annulés' => $synthese['annules'], 'RDV demain' => $synthese['demain']] as $libelle => $valeur)
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-600">{{ $libelle }}</p>
                        <p class="text-lg font-semibold">{{ $valeur }}</p>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
</section>
