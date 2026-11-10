<div class="space-y-4">
    <div wire:loading.delay class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg" role="status">Chargement…</div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Patients</h1>
        @can('patient.create')
            <button type="button" wire:click="nouveau" class="btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i>Nouveau patient</button>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        <section class="card lg:col-span-3" aria-label="Liste des patients">
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="Nom, téléphone ou numéro de fiche" class="field flex-1" aria-label="Rechercher">
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="afficherInactifs" class="rounded border-slate-300">Inactifs</label>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <li wire:key="p-{{ $patient->id }}">
                        <button type="button" wire:click="$set('fiche', {{ $patient->id }})" @class(['flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left text-sm hover:bg-slate-50', 'bg-primary-light' => $fiche === $patient->id])>
                            <span class="badge bg-slate-100 text-slate-600">N° {{ $patient->numero_fiche }}</span>
                            <span class="flex-1 font-medium">{{ $patient->nom_complet }}</span>
                            @unless ($patient->actif)<span class="badge bg-slate-200 text-slate-600">Inactif</span>@endunless
                            <span class="text-slate-500">{{ $patient->telephone }}</span>
                        </button>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-600">Aucun patient trouvé.</li>
                @endforelse
            </ul>
            <div class="mt-3">{{ $patients->links() }}</div>
        </section>

        <aside class="lg:col-span-2">
            @if ($formulaireOuvert)
                <form wire:submit="enregistrer" class="card space-y-3">
                    <h2 class="font-semibold">{{ $patientId ? 'Modifier la fiche' : 'Nouveau patient' }}</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="nom">Nom *</label>
                            <input id="nom" wire:model="form.nom" class="field">
                            @error('form.nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div><label class="label" for="prenom">Prénom</label><input id="prenom" wire:model="form.prenom" class="field"></div>
                        <div>
                            <label class="label" for="sexe">Sexe</label>
                            <select id="sexe" wire:model="form.sexe" class="field"><option value="">—</option><option value="F">Femme</option><option value="M">Homme</option></select>
                        </div>
                        <div>
                            <label class="label" for="ddn">Date de naissance</label>
                            <input id="ddn" type="date" wire:model="form.date_naissance" class="field">
                            @error('form.date_naissance')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div><label class="label" for="tel">Téléphone</label><input id="tel" type="tel" wire:model="form.telephone" class="field"></div>
                        <div><label class="label" for="nni">NNI</label><input id="nni" wire:model="form.nni" class="field"></div>
                    </div>
                    <div><label class="label" for="adresse">Adresse</label><input id="adresse" wire:model="form.adresse" class="field"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="assureur">Assurance</label>
                            <select id="assureur" wire:model="form.assureur_id" class="field"><option value="">Aucune</option>@foreach ($assureurs as $a)<option value="{{ $a->id }}">{{ $a->nom }} · {{ $a->pourcentage }}</option>@endforeach</select>
                            @error('form.assureur_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div><label class="label" for="matricule">Matricule assuré</label><input id="matricule" wire:model="form.matricule_assure" class="field"></div>
                    </div>
                    <div><label class="label" for="notes">Notes (allergies, antécédents…)</label><textarea id="notes" wire:model="form.notes" rows="3" class="field"></textarea></div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('formulaireOuvert', false)" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Enregistrer</button>
                    </div>
                </form>
            @elseif ($patientOuvert)
                <div class="card space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold">{{ $patientOuvert->nom_complet }}</h2>
                            <p class="text-sm text-slate-600">Fiche N° {{ $patientOuvert->numero_fiche }}</p>
                        </div>
                        @can('patient.edit')
                            <button type="button" wire:click="modifier({{ $patientOuvert->id }})" class="btn-secondary"><i class="ti ti-edit" aria-hidden="true"></i>Modifier</button>
                        @endcan
                    </div>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-slate-500">Téléphone</dt>
                        <dd>{{ $patientOuvert->telephone ?? '—' }}</dd>
                        <dt class="text-slate-500">Naissance</dt>
                        <dd>
                            {{ $patientOuvert->date_naissance?->format('d/m/Y') ?? '—' }}
                            @if ($patientOuvert->date_naissance) ({{ $patientOuvert->date_naissance->age }} ans) @endif
                        </dd>
                        <dt class="text-slate-500">NNI</dt>
                        <dd>{{ $patientOuvert->nni ?? '—' }}</dd>
                        <dt class="text-slate-500">Adresse</dt>
                        <dd>{{ $patientOuvert->adresse ?? '—' }}</dd>
                        <dt class="text-slate-500">Assurance</dt>
                        <dd>{{ $patientOuvert->assureur ? $patientOuvert->assureur->nom.' · '.$patientOuvert->assureur->pourcentage : '—' }}@if ($patientOuvert->matricule_assure) <span class="text-slate-500">({{ $patientOuvert->matricule_assure }})</span>@endif</dd>
                    </dl>
                    @if ($patientOuvert->notes)
                        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ $patientOuvert->notes }}</p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        @can('dossier.view')
                            <a href="{{ route('dossier', $patientOuvert) }}" wire:navigate class="btn-primary"><i class="ti ti-dental" aria-hidden="true"></i>Dossier dentaire</a>
                        @endcan
                        @can('rendez-vous.create')
                            <a href="{{ route('agenda', ['patient' => $patientOuvert->id]) }}" wire:navigate class="btn-secondary"><i class="ti ti-calendar-plus" aria-hidden="true"></i>Prendre RDV</a>
                        @endcan
                        @can('facture.edit')
                            <button type="button" x-on:click="$dispatch('consultation-sans-rdv', { patient: {{ $patientOuvert->id }} })" class="btn-secondary"><i class="ti ti-receipt" aria-hidden="true"></i>Consultation</button>
                            <a href="{{ route('factures', ['patient' => $patientOuvert->id]) }}" wire:navigate class="btn-secondary"><i class="ti ti-file-invoice" aria-hidden="true"></i>Facturer</a>
                        @endcan
                        @if (auth()->user()->can('patient.edit') || auth()->user()->can('patient.delete'))
                            <div x-data="{ ouvert: false }" x-on:click.outside="ouvert = false" class="relative">
                                <button type="button" x-on:click="ouvert = !ouvert" :aria-expanded="ouvert" class="btn-secondary"><i class="ti ti-dots" aria-hidden="true"></i>Plus d'actions</button>
                                <div x-show="ouvert" x-cloak class="absolute right-0 z-20 mt-1 w-52 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                                    @can('patient.edit')
                                        <button type="button" wire:click="basculerActif({{ $patientOuvert->id }})" x-on:click="ouvert = false" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50"><i class="ti ti-user-off" aria-hidden="true"></i>{{ $patientOuvert->actif ? 'Désactiver' : 'Réactiver' }}</button>
                                    @endcan
                                    @can('patient.delete')
                                        <button type="button" wire:click="supprimer({{ $patientOuvert->id }})" wire:confirm="Supprimer ce patient ? La fiche restera récupérable." x-on:click="ouvert = false" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50"><i class="ti ti-trash" aria-hidden="true"></i>Supprimer</button>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </div>
                    @can('facture.view')
                        @if ($patientOuvert->factures->isNotEmpty())
                            <div>
                                <h3 class="mb-1 text-sm font-medium">Dernières factures</h3>
                                <ul class="divide-y divide-slate-100 text-sm">
                                    @foreach ($patientOuvert->factures as $f)
                                        <li class="flex items-center gap-2 py-1.5">
                                            <a href="{{ route('factures', ['facture' => $f->id]) }}" wire:navigate class="flex-1 text-primary hover:underline">{{ $f->numero ?? 'Devis' }}</a>
                                            <span>{{ number_format($f->total, 0, ',', ' ') }} MRU</span>
                                            @if ($f->statut === 'validee' && !$f->estSoldee())<span class="badge bg-amber-100 text-amber-800">Reste {{ number_format($f->restePatient() + $f->restePec(), 0, ',', ' ') }}</span>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endcan
                    <div>
                        <h3 class="mb-1 text-sm font-medium">Derniers rendez-vous</h3>
                        <ul class="divide-y divide-slate-100 text-sm">
                            @forelse ($patientOuvert->rendezVous as $rdv)
                                <li class="flex items-center gap-2 py-1.5">
                                    <span class="w-24">{{ $rdv->date->format('d/m/Y') }}</span>
                                    <span class="w-12">{{ $rdv->heure_courte }}</span>
                                    <span class="flex-1 truncate text-slate-600">Dr {{ $rdv->dentiste->name }}@if ($rdv->acte_prevu) · {{ $rdv->acte_prevu }} @endif</span>
                                    <span class="badge {{ $rdv->statut->couleur() }}">{{ $rdv->statut->label() }}</span>
                                </li>
                            @empty
                                <li class="py-1.5 text-slate-600">Aucun rendez-vous.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @else
                <div class="card text-sm text-slate-600">Sélectionnez un patient pour afficher sa fiche.</div>
            @endif
        </aside>
    </div>
</div>
