<div class="space-y-4">
    <div wire:loading.delay class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg" role="status">Mise à jour de l'agenda…</div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Agenda</h1>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="decaler(-1)" class="btn-secondary !px-3" aria-label="Jour précédent"><i class="ti ti-chevron-left" aria-hidden="true"></i></button>
            <input type="date" wire:model.live="date" class="field !w-auto" aria-label="Date">
            <button type="button" wire:click="decaler(1)" class="btn-secondary !px-3" aria-label="Jour suivant"><i class="ti ti-chevron-right" aria-hidden="true"></i></button>
            @unless ($jour->isToday())
                <button type="button" wire:click="$set('date', '{{ today()->format('Y-m-d') }}')" class="btn-secondary">Aujourd'hui</button>
            @endunless
        </div>
    </div>
    <nav class="grid grid-cols-7 gap-1" aria-label="Jours de la semaine">
        @foreach ($semaine as $j)
            <button type="button" wire:click="$set('date', '{{ $j['cle'] }}')" wire:key="j-{{ $j['cle'] }}"
                @class(['flex flex-col items-center rounded-lg border py-2 text-xs transition',
                    'border-primary bg-primary text-white' => $j['cle'] === $date,
                    'border-slate-200 bg-white hover:border-primary' => $j['cle'] !== $date && $j['ouvert'],
                    'border-slate-100 bg-slate-100 text-slate-400' => $j['cle'] !== $date && !$j['ouvert']])
                @if ($j['cle'] === $date) aria-current="date" @endif>
                <span class="uppercase">{{ $j['date']->isoFormat('ddd') }}</span>
                <span class="text-base font-semibold">{{ $j['date']->day }}</span>
                <span>{{ $j['rdv'] ? $j['rdv'].' RDV' : ($j['ouvert'] ? '·' : 'fermé') }}</span>
            </button>
        @endforeach
    </nav>
    <p class="text-sm text-slate-600 first-letter:uppercase">{{ $jour->isoFormat('dddd D MMMM YYYY') }}@unless ($ouvert) · <span class="text-amber-700">cabinet fermé ce jour-là</span>@endunless</p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @can('rendez-vous.create')
            <form wire:submit="creer" class="card space-y-3 lg:order-2" aria-label="Nouveau rendez-vous">
                <h2 class="font-semibold">Nouveau rendez-vous</h2>
                <div class="relative">
                    <label class="label" for="patient">Patient *</label>
                    @if (!$creation)
                        <input id="patient" type="search" wire:model.live.debounce.300ms="recherchePatient" placeholder="Nom, téléphone ou N° de fiche" class="field" autocomplete="off">
                        @if ($patientId)
                            <p class="mt-1 text-xs text-emerald-700"><i class="ti ti-check" aria-hidden="true"></i> Patient sélectionné</p>
                        @endif
                        @if ($suggestions->isNotEmpty())
                            <ul class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                                @foreach ($suggestions as $patient)
                                    <li><button type="button" wire:click="choisirPatient({{ $patient->id }})" class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50">{{ $patient->nom_complet }} <span class="text-slate-500">· N° {{ $patient->numero_fiche }}</span></button></li>
                                @endforeach
                                @can('patient.create')
                                    <li class="border-t border-slate-100"><button type="button" wire:click="nouveauPatient" class="w-full px-3 py-2 text-left text-sm text-primary hover:bg-slate-50"><i class="ti ti-user-plus" aria-hidden="true"></i> Nouveau patient</button></li>
                                @endcan
                            </ul>
                        @elseif (!$patientId && mb_strlen(trim($recherchePatient)) >= 2)
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 text-sm">
                                <span class="text-slate-600">Aucun patient trouvé.</span>
                                @can('patient.create')
                                    <button type="button" wire:click="nouveauPatient" class="font-medium text-primary hover:underline"><i class="ti ti-user-plus" aria-hidden="true"></i> Créer ce patient</button>
                                @endcan
                            </div>
                        @endif
                    @else
                        <fieldset class="space-y-2 rounded-lg bg-slate-50 p-3">
                            <legend class="px-1 text-sm font-medium text-slate-700">Nouveau patient <span class="font-normal text-slate-500">· complétez la fiche plus tard</span></legend>
                            <div class="grid grid-cols-2 gap-2">
                                <input wire:model="nouveau.nom" class="field" placeholder="Nom *" aria-label="Nom" autocomplete="off">
                                <input wire:model="nouveau.prenom" class="field" placeholder="Prénom" aria-label="Prénom" autocomplete="off">
                                <input wire:model="nouveau.telephone" type="tel" class="field col-span-2" placeholder="Téléphone" aria-label="Téléphone" autocomplete="off">
                            </div>
                            @error('nouveau.nom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <button type="button" wire:click="annulerCreation" class="text-xs text-slate-600 hover:underline">Chercher un patient existant</button>
                        </fieldset>
                    @endif
                    @error('patientId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="dentiste">Dentiste *</label>
                    <select id="dentiste" wire:model.live="dentisteId" class="field">
                        @foreach ($dentistes as $d)<option value="{{ $d->id }}">Dr {{ $d->name }}</option>@endforeach
                    </select>
                    @error('dentisteId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Date</label>
                        <p class="field bg-slate-50">{{ $jour->format('d/m/Y') }}</p>
                        @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="heure">Heure *</label>
                        <input id="heure" type="time" step="300" wire:model="heure" class="field">
                        @error('heure')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                @if ($jour->gte(today()))
                    <div>
                        <p class="label">Créneaux libres <span class="font-normal text-slate-500">· {{ $duree }} min</span></p>
                        @if ($creneaux)
                            <div class="flex max-h-40 flex-wrap gap-1 overflow-y-auto">
                                @foreach ($creneaux as $c)
                                    <button type="button" wire:click="choisirHeure('{{ $c }}')" wire:key="c-{{ $c }}" @class(['rounded-md border px-2 py-1 text-xs font-medium', 'border-primary bg-primary text-white' => $heure === $c, 'border-slate-200 hover:border-primary' => $heure !== $c])>{{ $c }}</button>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-500">{{ $ouvert ? 'Aucun créneau libre ce jour-là. Vous pouvez saisir une heure manuellement.' : 'Cabinet fermé ce jour-là.' }}</p>
                        @endif
                    </div>
                @endif
                <div>
                    <label class="label" for="acte">Acte prévu</label>
                    <input id="acte" wire:model="acte" placeholder="Détartrage, contrôle, extraction…" class="field">
                </div>
                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled"><i class="ti ti-calendar-plus" aria-hidden="true"></i>Planifier</button>
            </form>
        @endcan

        <section class="space-y-3 lg:order-1 lg:col-span-2" aria-label="Rendez-vous du jour">
            @forelse ($rdvs as $liste)
                <div class="card">
                    <h2 class="mb-2 text-sm font-medium text-slate-700">Dr {{ $liste->first()->dentiste->name }} · {{ $liste->where('statut', '!=', \App\Enums\StatutRdv::Annule)->count() }} RDV</h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($liste as $rdv)
                            <li wire:key="rdv-{{ $rdv->id }}" @class(['flex flex-wrap items-center gap-3 py-2 text-sm', 'opacity-60' => $rdv->statut === \App\Enums\StatutRdv::Annule])>
                                <span class="w-12 font-medium">{{ $rdv->heure_courte }}</span>
                                <span class="min-w-[11rem] flex-1">
                                    <a href="{{ route('patients', ['fiche' => $rdv->patient_id]) }}" wire:navigate class="hover:underline">{{ $rdv->patient->nom_complet }}</a>
                                    @if ($rdv->acte_prevu)<span class="text-slate-500"> · {{ $rdv->acte_prevu }}</span>@endif
                                </span>
                                <span class="badge {{ $rdv->statut->couleur() }}">{{ $rdv->statut->label() }}</span>
                                @if ($jour->isToday() && $rdv->statut !== \App\Enums\StatutRdv::Planifie)
                                    @include('livewire.partials.puce-consultation')
                                @endif
                                @if ($rdv->statut === \App\Enums\StatutRdv::Planifie && $jour->isToday())
                                    <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'arrive')" class="rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary-light">Arrivé</button>
                                @endif
                                @if ($rdv->statut->peutPasserA(\App\Enums\StatutRdv::Annule))
                                    <button type="button" wire:click="changerStatut({{ $rdv->id }}, 'annule')" wire:confirm="Annuler ce rendez-vous ?" class="rounded-md px-2 py-1 text-xs text-red-700 hover:bg-red-50">Annuler</button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="card text-sm text-slate-600">Aucun rendez-vous ce jour-là.</div>
            @endforelse
        </section>
    </div>
</div>
