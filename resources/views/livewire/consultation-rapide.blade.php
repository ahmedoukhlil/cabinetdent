@php $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU'; @endphp
<div>
@if ($etape)
    <div class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="cs-titre"
         x-data x-on:keydown.escape.window="$wire.fermer()">
        <div class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 id="cs-titre" class="font-semibold">
                        {{ $etape === 'patient' ? 'Consultation sans rendez-vous' : ($etape === 'fait' ? 'Consultation encaissée' : 'Facturer la consultation') }}
                    </h2>
                    @if ($rdv)<p class="text-sm text-slate-600">{{ $rdv->patient->nom_complet }} · Dr {{ $rdv->dentiste->name }}</p>@endif
                </div>
                <button type="button" wire:click="fermer" class="text-slate-400 hover:text-slate-700" aria-label="Fermer"><i class="ti ti-x" aria-hidden="true"></i></button>
            </div>

            {{-- Étape 1 (sans RDV) : patient et dentiste --}}
            @if ($etape === 'patient')
                <form wire:submit="inscrire" class="space-y-3">
                    @if ($patientChoisi && !$creation)
                        <div class="flex items-center gap-3 rounded-lg border border-primary bg-primary/5 p-3 text-sm">
                            <i class="ti ti-user-check text-lg text-primary" aria-hidden="true"></i>
                            <span class="flex-1"><span class="font-medium">{{ $patientChoisi->nom_complet }}</span><span class="block text-xs text-slate-500">{{ $patientChoisi->telephone ?: 'Sans téléphone' }}{{ $patientChoisi->assureur ? ' · '.$patientChoisi->assureur->nom : '' }}</span></span>
                            <button type="button" wire:click="$set('patientId', null)" class="text-xs text-slate-600 hover:underline">Changer</button>
                        </div>
                    @elseif (!$creation)
                        <div>
                            <label class="label" for="cs-recherche">Patient</label>
                            <div class="relative">
                                <i class="ti ti-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                <input id="cs-recherche" type="search" wire:model.live.debounce.250ms="recherche" class="field !pl-9" placeholder="Nom, téléphone ou n° de fiche…" autocomplete="off" autofocus>
                            </div>
                            @if ($patients->isNotEmpty())
                                <ul class="mt-1 divide-y divide-slate-100 rounded-lg border border-slate-200 text-sm">
                                    @foreach ($patients as $p)
                                        <li wire:key="cs-p-{{ $p->id }}"><button type="button" wire:click="choisirPatient({{ $p->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50">
                                            <span class="flex-1">{{ $p->nom_complet }}</span><span class="text-xs text-slate-500">{{ $p->telephone }}</span>
                                        </button></li>
                                    @endforeach
                                </ul>
                            @elseif (mb_strlen(trim($recherche)) >= 2)
                                <p class="mt-1 text-sm text-slate-600">Aucun patient trouvé.</p>
                            @endif
                            @if ($user->aPermission('patient.create'))
                                <button type="button" wire:click="nouveauPatient" class="mt-2 text-sm text-primary hover:underline"><i class="ti ti-user-plus" aria-hidden="true"></i> Nouveau patient</button>
                            @endif
                        </div>
                    @else
                        <fieldset class="space-y-2 rounded-lg bg-slate-50 p-3">
                            <legend class="px-1 text-sm font-medium text-slate-700">Nouveau patient <span class="font-normal text-slate-500">· complétez la fiche plus tard</span></legend>
                            <div class="grid grid-cols-2 gap-2">
                                <input wire:model="nouveau.nom" class="field" placeholder="Nom" aria-label="Nom">
                                <input wire:model="nouveau.prenom" class="field" placeholder="Prénom" aria-label="Prénom">
                                <input wire:model="nouveau.telephone" type="tel" class="field col-span-2" placeholder="Téléphone" aria-label="Téléphone">
                            </div>
                            @error('nouveau.nom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <button type="button" wire:click="$set('creation', false)" class="text-xs text-slate-600 hover:underline">Chercher un patient existant</button>
                        </fieldset>
                    @endif
                    @error('patientId')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                    <div>
                        <label class="label" for="cs-dentiste">Dentiste</label>
                        <select id="cs-dentiste" wire:model="dentisteId" class="field">
                            @foreach ($dentistes as $d)<option value="{{ $d->id }}">Dr {{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="fermer" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary">Continuer <i class="ti ti-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </form>
            @endif

            {{-- Étape 2 : facture et encaissement --}}
            @if ($etape === 'facture' && $rdv)
                <form wire:submit="facturer" class="space-y-4">
                    <fieldset class="space-y-2">
                        <legend class="label">Consultation</legend>
                        @forelse ($actes as $a)
                            @php $choisi = (string) $a->id === (string) $consultation['acte_id']; @endphp
                            <label wire:key="cs-a-{{ $a->id }}" @class(['flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm', 'border-primary bg-primary/5' => $choisi, 'border-slate-200 hover:bg-slate-50' => !$choisi])>
                                <input type="radio" wire:model.live="consultation.acte_id" value="{{ $a->id }}" class="text-primary">
                                <span class="flex-1">{{ $a->libelle }}</span>
                                <span class="font-medium tabular-nums">{{ $mru($a->prixPour($rdv->patient->assureur_id)) }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-600">Aucun acte de type consultation. Créez-en un dans Paramètres › Tarifs.</p>
                        @endforelse
                        @error('consultation.acte_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </fieldset>

                    @if ($apercu)
                        <dl class="grid grid-cols-2 gap-y-1 rounded-lg bg-slate-50 p-3 text-sm">
                            <dt class="text-slate-600">Total</dt><dd class="text-right tabular-nums">{{ $mru($apercu['total']) }}</dd>
                            @if ($apercu['assureur'])
                                <dt class="text-slate-600">Part {{ $apercu['assureur'] }}</dt><dd class="text-right tabular-nums">− {{ $mru($apercu['pec']) }}</dd>
                            @endif
                            <dt class="font-medium">À payer par le patient</dt><dd class="text-right text-base font-semibold tabular-nums">{{ $mru($apercu['patient']) }}</dd>
                        </dl>
                    @endif

                    @php $peutEncaisser = $user->aPermission('paiement.create') && ($apercu['patient'] ?? 1) > 0; @endphp
                    @if (!$user->aPermission('paiement.create'))
                        <p class="text-xs text-slate-500">La facture sera créée ; l'encaissement se fait à l'accueil.</p>
                    @elseif ($peutEncaisser)
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="consultation.encaisser" class="rounded border-slate-300"> Encaisser maintenant (entrée en caisse)</label>
                            @if ($consultation['encaisser'])
                                <div class="grid grid-cols-2 gap-2">
                                    <select wire:model="consultation.mode" class="field" aria-label="Mode de paiement">
                                        @foreach (\App\Models\ModePaiement::actifs() as $cle => $libelle)<option value="{{ $cle }}">{{ $libelle }}</option>@endforeach
                                    </select>
                                    <input wire:model="consultation.reference" class="field" placeholder="Référence (optionnel)" aria-label="Référence du paiement">
                                </div>
                                @error('consultation.mode')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="fermer" class="btn-secondary">Plus tard</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="facturer" @disabled($actes->isEmpty())>
                            <i class="ti ti-receipt" aria-hidden="true"></i> {{ $consultation['encaisser'] && $peutEncaisser ? 'Facturer et encaisser' : 'Facturer' }}
                        </button>
                    </div>
                </form>
            @endif

            {{-- Étape 3 : confirmation et reçu --}}
            @if ($etape === 'fait' && $paiement)
                <div class="space-y-4 text-center">
                    <i class="ti ti-circle-check text-5xl text-emerald-600" aria-hidden="true"></i>
                    <p class="text-sm">{{ $mru($paiement->montant) }} encaissés · {{ \App\Models\ModePaiement::libelles()[$paiement->mode] ?? $paiement->mode }}<br><span class="text-slate-500">{{ $paiement->patient->nom_complet }} · facture {{ $paiement->facture->numero }}</span></p>
                    <div class="flex justify-center gap-2">
                        <button type="button" wire:click="fermer" class="btn-secondary">Fermer</button>
                        <a href="{{ route('paiements.recu', $paiement) }}" target="_blank" rel="noopener" class="btn-primary" x-on:click="$wire.fermer()"><i class="ti ti-printer" aria-hidden="true"></i> Imprimer le reçu</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
</div>
