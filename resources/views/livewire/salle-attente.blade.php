<div wire:poll.30s class="space-y-4">
    <div wire:loading.delay class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg" role="status">Mise à jour de la salle…</div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Salle d'attente</h1>
        @if ($consultationsPossibles && $user->aPermission('facture.edit'))
            <button type="button" x-on:click="$dispatch('consultation-sans-rdv')" class="btn-primary sm:order-last"><i class="ti ti-user-plus" aria-hidden="true"></i> Consultation sans RDV</button>
        @endif
        @if ($user->aPermission('rendez-vous.edit'))
            <select wire:model.live="dentisteId" class="field !w-auto" aria-label="Filtrer par dentiste">
                <option value="">Tous les dentistes</option>
                @foreach ($dentistes as $d)<option value="{{ $d->id }}">Dr {{ $d->name }}</option>@endforeach
            </select>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="card" aria-labelledby="col-attendus">
            <h2 id="col-attendus" class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700"><i class="ti ti-calendar-time" aria-hidden="true"></i>Attendus · {{ $attendus->count() }}</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($attendus as $rdv)
                    <li wire:key="a-{{ $rdv->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                        <span class="w-12 font-medium">{{ $rdv->heure_courte }}</span>
                        <a href="{{ route('patients', ['fiche' => $rdv->patient_id]) }}" wire:navigate class="min-w-[11rem] flex-1 hover:underline"><span class="block font-medium">{{ $rdv->patient->nom_complet }}</span><span class="block text-xs text-slate-500">Dr {{ $rdv->dentiste->name }}</span></a>
                        <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'arrive')" class="btn-secondary !px-3 !py-1.5 text-xs">Arrivé</button>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-600">Plus personne d'attendu.</li>
                @endforelse
            </ul>
        </section>

        <section class="card" aria-labelledby="col-arrives">
            <h2 id="col-arrives" class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700"><i class="ti ti-armchair" aria-hidden="true"></i>En attente · {{ $arrives->count() }}</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($arrives as $rdv)
                    @php($minutes = (int) $rdv->arrive_at?->diffInMinutes(now()))
                    <li wire:key="w-{{ $rdv->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                        <a href="{{ route('patients', ['fiche' => $rdv->patient_id]) }}" wire:navigate class="min-w-[11rem] flex-1 hover:underline"><span class="block font-medium">{{ $rdv->patient->nom_complet }}</span><span class="block text-xs text-slate-500">RDV {{ $rdv->heure_courte }} · Dr {{ $rdv->dentiste->name }}</span></a>
                        @include('livewire.partials.puce-consultation')
                        <span @class(['badge', 'bg-amber-100 text-amber-800' => $minutes >= 20, 'bg-slate-100 text-slate-600' => $minutes < 20])>{{ $minutes }} min</span>
                        @if ($user->aPermission('soins.manage') && ($user->aPermission('rendez-vous.edit') || $rdv->dentiste_id === $user->id))
                            <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'en_cours')" class="btn-primary !px-3 !py-1.5 text-xs">Faire entrer</button>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-600">Personne en attente.</li>
                @endforelse
            </ul>
        </section>

        <section class="card" aria-labelledby="col-soins">
            <h2 id="col-soins" class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700"><i class="ti ti-dental" aria-hidden="true"></i>En soins · {{ $enSoins->count() }}</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($enSoins as $rdv)
                    <li wire:key="s-{{ $rdv->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                        <a href="{{ route('patients', ['fiche' => $rdv->patient_id]) }}" wire:navigate class="min-w-[11rem] flex-1 hover:underline"><span class="block font-medium">{{ $rdv->patient->nom_complet }}</span><span class="block text-xs text-slate-500">Dr {{ $rdv->dentiste->name }} · depuis {{ (int) $rdv->debut_at?->diffInMinutes(now()) }} min</span></a>
                        @include('livewire.partials.puce-consultation')
                        @if ($user->aPermission('soins.manage') && ($user->aPermission('rendez-vous.edit') || $rdv->dentiste_id === $user->id))
                            <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'termine')" class="btn-secondary !px-3 !py-1.5 text-xs">Terminer</button>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-600">Aucun patient au fauteuil.</li>
                @endforelse
            </ul>
        </section>

        <section class="card" aria-labelledby="col-termines">
            <h2 id="col-termines" class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700"><i class="ti ti-circle-check" aria-hidden="true"></i>Terminés · {{ $termines->count() }}</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($termines as $rdv)
                    <li wire:key="t-{{ $rdv->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                        <a href="{{ route('patients', ['fiche' => $rdv->patient_id]) }}" wire:navigate class="min-w-[11rem] flex-1 hover:underline"><span class="block font-medium">{{ $rdv->patient->nom_complet }}</span><span class="block text-xs text-slate-500">Dr {{ $rdv->dentiste->name }} · fini à {{ $rdv->fin_at?->format('H:i') }}</span></a>
                        @include('livewire.partials.puce-consultation')
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-600">Aucun patient terminé.</li>
                @endforelse
            </ul>
        </section>
    </div>

</div>
