@php
    use App\Support\Odontologie;
    use App\Models\PlanTraitement;
    $montant = fn ($v) => number_format($v, 0, ',', ' ').' MRU';
@endphp
<div class="space-y-4" x-data="{ multiple: false, onglet: localStorage.getItem('sysdental-dossier-onglet') || 'etat' }" x-effect="localStorage.setItem('sysdental-dossier-onglet', onglet)">
    <div wire:loading.delay class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg" role="status">Mise à jour du dossier…</div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('patients', ['fiche' => $patient->id]) }}" wire:navigate class="text-sm text-primary hover:underline"><i class="ti ti-arrow-left" aria-hidden="true"></i> Fiche patient</a>
            <h1 class="text-lg font-semibold">Dossier dentaire · {{ $patient->nom_complet }}</h1>
            <p class="text-sm text-slate-600">
                Fiche N° {{ $patient->numero_fiche }}
                @if ($patient->date_naissance) · {{ $patient->date_naissance->age }} ans @endif
            </p>
        </div>
        <a href="{{ route('dossier.imprimer', $patient) }}" target="_blank" class="btn-secondary"><i class="ti ti-printer" aria-hidden="true"></i>Imprimer le dossier</a>
        @if ($patient->notes)
            <p class="max-w-md rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"><i class="ti ti-alert-triangle" aria-hidden="true"></i> {{ $patient->notes }}</p>
        @endif
    </div>

    {{-- Odontogramme : vue anatomique (react-odontogram) ou vue détaillée par faces --}}
    <section class="card" aria-labelledby="odonto-titre" x-data="{ vue: localStorage.getItem('sysdental-vue-odonto') || 'arcade' }" x-init="$watch('vue', v => localStorage.setItem('sysdental-vue-odonto', v))">
        <div class="sticky top-0 z-10 -mx-2 mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white/95 px-2 py-2 backdrop-blur">
            <h2 id="odonto-titre" class="font-medium">Odontogramme <span class="text-sm font-normal text-slate-500">· notation FDI</span></h2>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <div class="inline-flex rounded-lg border border-slate-200 p-0.5" role="group" aria-label="Type de vue">
                    <button type="button" x-on:click="vue = 'arcade'" :aria-pressed="vue === 'arcade'" :class="vue === 'arcade' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50'" class="rounded-md px-2.5 py-1"><i class="ti ti-dental" aria-hidden="true"></i> Arcades</button>
                    <button type="button" x-on:click="vue = 'faces'" :aria-pressed="vue === 'faces'" :class="vue === 'faces' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50'" class="rounded-md px-2.5 py-1"><i class="ti ti-layout-grid" aria-hidden="true"></i> Par faces</button>
                </div>
                <label x-show="vue === 'faces'" class="flex items-center gap-2 text-slate-600">
                    <input type="checkbox" x-model="multiple" class="rounded border-slate-300"> Sélection multiple
                </label>
                @if ($selection)
                    <span class="badge bg-primary-light text-primary">Sélection : {{ implode(', ', $selection) }}</span>
                    <button type="button" wire:click="viderSelection" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">Effacer</button>
                @endif
            </div>
        </div>

        {{-- Sélection rapide par groupe : un appui ajoute le groupe, un second le retire --}}
        <div class="mb-3 space-y-1.5" role="group" aria-label="Sélection rapide">
            @php $groupes = Odontologie::groupes(); @endphp
            @foreach ([['bouche', 'sup', 'inf'], ['q1', 'q2', 'q4', 'q3'], ['s1', 's2', 's3', 's6', 's5', 's4']] as $rangee)
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($rangee as $cle)
                        @php [$court, $long, $dentsGroupe] = $groupes[$cle]; $actif = array_diff($dentsGroupe, $selection) === []; @endphp
                        <button type="button" wire:click="selectionnerGroupe('{{ $cle }}')" title="{{ $long }}" aria-label="{{ $long }}" aria-pressed="{{ $actif ? 'true' : 'false' }}"
                            @class(['rounded-md border px-2.5 py-1 text-xs font-medium transition', 'border-primary bg-primary text-white' => $actif, 'border-slate-200 bg-white text-slate-700 hover:border-primary' => !$actif])>{{ $court }}</button>
                    @endforeach
                </div>
            @endforeach
            <p class="text-xs text-slate-500">Q1–Q4 : hémi-arcades · S1–S6 : sextants. Un second appui retire le groupe.</p>
        </div>

        {{-- Vue anatomique : cliquer sur les dents pour les sélectionner (plusieurs possibles) --}}
        <div x-show="vue === 'arcade'" x-data="odontogramme(false)" class="odontogramme-sysdental">
            <span class="hidden" data-odontogramme="{{ json_encode($odontogramme) }}"></span>
            <div wire:ignore data-scene class="mx-auto min-h-[260px] max-w-[320px]">
                <p class="py-16 text-center text-sm text-slate-400">Chargement de l'odontogramme…</p>
            </div>
            <p class="mt-1 text-center text-xs text-slate-500">Touchez les dents pour les sélectionner, puis renseignez leur état ou ajoutez un soin ci-dessous.</p>
        </div>

        {{-- Vue détaillée : état face par face --}}
        <div x-show="vue === 'faces'" x-cloak>
            <div class="overflow-x-auto pb-1">
                <div class="mx-auto w-max space-y-2">
                    @foreach ([Odontologie::ARCADE_SUPERIEURE, Odontologie::ARCADE_INFERIEURE] as $i => $arcade)
                        <div class="flex items-end gap-1">
                            @foreach ($arcade as $j => $numero)
                                @if ($j === 8)<span class="mx-1 w-px self-stretch bg-slate-300" aria-hidden="true"></span>@endif
                                <x-dent :numero="$numero" :dent="$dents->get($numero)" :selectionnee="in_array($numero, $selection, true)"
                                    x-on:click="$wire.basculerDent({{ $numero }}, multiple || $event.shiftKey || $event.ctrlKey || $event.metaKey)" />
                            @endforeach
                        </div>
                        @if ($i === 0)<div class="border-t border-slate-300" aria-hidden="true"></div>@endif
                    @endforeach
                </div>
            </div>

            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600" aria-label="Légende">
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm bg-red-600 align-middle"></span>Carie</li>
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm bg-blue-600 align-middle"></span>Obturation</li>
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm border-2 border-amber-500 align-middle"></span>Prothèse</li>
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm border-2 border-teal-600 align-middle"></span>Implant</li>
                <li><span class="mr-1 inline-block h-3 w-1 bg-violet-600 align-middle"></span>Traitement endodontique</li>
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm bg-red-50 ring-1 ring-red-300 align-middle"></span>À traiter</li>
                <li><span class="mr-1 inline-block h-3 w-3 rounded-sm border border-dashed border-slate-400 bg-slate-100 align-middle"></span>Absente</li>
            </ul>
        </div>
    </section>

    <nav class="sticky top-2 z-20 flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm" aria-label="Sections du dossier" role="tablist">
        @foreach ([
            'etat' => ['État dentaire', 'ti-dental'],
            'plan' => ['Plan de traitement', 'ti-clipboard-list'],
            'documents' => ['Documents', 'ti-file-medical'],
            'historique' => ['Historique', 'ti-history'],
        ] as $cle => [$libelle, $icone])
            <button type="button" x-on:click="onglet = '{{ $cle }}'" :aria-selected="onglet === '{{ $cle }}'" :class="onglet === '{{ $cle }}' ? 'bg-primary text-white' : 'text-slate-700 hover:bg-slate-100'" class="flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium" role="tab"><i class="ti {{ $icone }}" aria-hidden="true"></i>{{ $libelle }}</button>
        @endforeach
    </nav>

    <div>
        {{-- État des dents sélectionnées --}}
        <section x-show="onglet === 'etat'" x-cloak class="card" aria-labelledby="etat-titre">
            @if (!$selection)
                <h2 id="etat-titre" class="font-medium">État dentaire</h2>
                <p class="mt-2 text-sm text-slate-600">Touchez une dent pour la renseigner. Cochez « Sélection multiple » (ou maintenez Maj) pour appliquer le même état à plusieurs dents.</p>
            @else
                <h2 id="etat-titre" class="font-medium">
                    {{ count($selection) > 1 ? 'Dents '.implode(', ', $selection) : 'Dent '.$selection[0] }}
                </h2>
                @if (count($selection) > 1)
                    <p class="mt-1 text-xs text-amber-700">L'état saisi remplacera celui de chacune des {{ count($selection) }} dents.</p>
                @endif
                <form wire:submit="enregistrer" class="mt-3 space-y-3">
                    <fieldset @disabled(!$peutModifier) class="space-y-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach (['presence' => ['Présence', Odontologie::PRESENCE], 'restauration' => ['Prothèse / restauration', Odontologie::RESTAURATION], 'endodontie' => ['Endodontie', Odontologie::ENDODONTIE]] as $champ => [$libelle, $options])
                                <div>
                                    <label class="label" for="{{ $champ }}">{{ $libelle }}</label>
                                    <select id="{{ $champ }}" wire:model.live="etat.{{ $champ }}" class="field">
                                        @foreach ($options as $valeur => $texte)<option value="{{ $valeur }}">{{ $texte }}</option>@endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        @if ($etat['presence'] === 'presente')
                            <div>
                                <p class="label">Faces <span class="font-normal text-slate-500">· touchez pour alterner saine → carie → obturation</span></p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (Odontologie::FACES as $face)
                                        @php $ef = $etat['faces'][$face] ?? 'sain'; @endphp
                                        <button type="button" wire:click="cyclerFace('{{ $face }}')"
                                            @class(['rounded-lg border px-3 py-2 text-left text-sm', 'border-slate-300 bg-white' => $ef === 'sain', 'border-red-300 bg-red-50 text-red-800' => $ef === 'carie', 'border-blue-300 bg-blue-50 text-blue-800' => $ef === 'obturation'])>
                                            <span class="font-semibold">{{ $face }}</span>
                                            <span class="block text-xs">{{ Odontologie::libelleFace($face, $selection[0]) }} · {{ Odontologie::ETATS_FACE[$ef] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <details {{ $etat['constatations'] ? 'open' : '' }}>
                            <summary class="cursor-pointer text-sm font-medium text-slate-700">Constatations ({{ count($etat['constatations']) }})</summary>
                            <div class="mt-2 space-y-2">
                                @foreach (Odontologie::CONSTATATIONS as $groupe => $items)
                                    <div>
                                        <p class="text-xs font-medium text-slate-500">{{ $groupe }}</p>
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach ($items as $code => $texte)
                                                <label class="cursor-pointer">
                                                    <input type="checkbox" value="{{ $code }}" wire:model="etat.constatations" class="peer sr-only">
                                                    <span class="inline-block rounded-full border border-slate-300 px-2.5 py-1 text-xs peer-checked:border-primary peer-checked:bg-primary-light peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary">{{ $texte }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>

                        <div>
                            <label class="label" for="notes-dent">Notes</label>
                            <textarea id="notes-dent" wire:model="etat.notes" rows="2" class="field"></textarea>
                        </div>
                    </fieldset>
                    @if ($peutModifier)
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled"><i class="ti ti-device-floppy" aria-hidden="true"></i>Enregistrer</button>
                        </div>
                    @endif
                </form>
            @endif
        </section>

        {{-- Plan de traitement --}}
        <section x-show="onglet === 'plan'" x-cloak class="card" aria-labelledby="plan-titre">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="plan-titre" class="font-medium">Plan de traitement</h2>
                @if ($plan)
                    <span class="badge bg-slate-100 text-slate-700">{{ PlanTraitement::STATUTS[$plan->statut] }}</span>
                @endif
            </div>

            @if ($plan && $plan->lignes->isNotEmpty())
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($plan->lignes as $l)
                        <li wire:key="l-{{ $l->id }}" class="flex items-start gap-2 py-2 text-sm">
                            @if ($peutModifier && in_array($plan->statut, ['accepte', 'en_cours']))
                                <input type="checkbox" @checked($l->statut === 'realise') wire:click="basculerRealise({{ $l->id }})" class="mt-1 rounded border-slate-300" aria-label="Marquer réalisé : {{ $l->libelle }}">
                            @endif
                            <div class="min-w-0 flex-1">
                                <p @class(['font-medium', 'text-slate-500 line-through' => $l->statut === 'realise'])>{{ $l->libelle }}</p>
                                <p class="text-xs text-slate-500">
                                    @if ($l->dents)
                                        <button type="button" wire:click="selectionnerLigne({{ $l->id }})" class="text-primary hover:underline" title="Afficher ces dents sur l'odontogramme"><i class="ti ti-dental" aria-hidden="true"></i> Dent{{ count($l->dents) > 1 ? 's' : '' }} {{ implode(', ', $l->dents) }}</button>
                                    @else
                                        Sans dent
                                    @endif
                                    @if ($l->quantite != 1) · × {{ rtrim(rtrim(number_format($l->quantite, 2, ',', ''), '0'), ',') }} @endif
                                    @if ($l->realise_at) · réalisé le {{ $l->realise_at->format('d/m/Y') }} @endif
                                </p>
                            </div>
                            <span class="whitespace-nowrap">{{ $montant($l->montant()) }}</span>
                            @php $fl = $l->factureLigne; $facturee = $fl && $fl->facture && $fl->facture->statut !== 'annulee'; @endphp
                            @if ($facturee)
                                <a href="{{ route('factures', ['facture' => $fl->facture_id]) }}" wire:navigate class="badge bg-emerald-100 text-emerald-800 hover:underline">{{ $fl->facture->numero ? 'Facturé '.$fl->facture->numero : 'Au devis' }}</a>
                            @elseif ($peutFacturer && in_array($plan->statut, ['accepte', 'en_cours', 'termine']))
                                <button type="button" wire:click="facturer({{ $l->id }})" class="rounded-md px-2 py-0.5 text-xs font-medium text-primary hover:bg-primary-light">Facturer</button>
                            @endif
                            @if ($peutModifier && $l->statut !== 'realise' && !$facturee && $plan->estModifiable())
                                <button type="button" wire:click="supprimerLigne({{ $l->id }})" wire:confirm="Retirer ce soin du plan ?" class="text-slate-400 hover:text-red-600" aria-label="Retirer {{ $l->libelle }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-sm font-medium">
                    <span>Total estimé</span><span>{{ $montant($plan->total()) }}</span>
                </p>
            @else
                <p class="mt-2 text-sm text-slate-600">Aucun soin prévu. Sélectionnez des dents puis ajoutez un soin.</p>
            @endif

            @if ($peutModifier && (!$plan || $plan->estModifiable()))
                <form wire:submit="ajouterLigne" class="mt-4 space-y-2 rounded-lg bg-slate-50 p-3">
                    <p class="text-sm font-medium">Ajouter un soin</p>
                    <div class="grid grid-cols-6 gap-2">
                        <div class="relative col-span-6 sm:col-span-3"
                             x-data="{ ouvert: true, actif: 0 }"
                             x-on:click.outside="ouvert = false"
                             x-on:keydown.escape="ouvert = false">
                            <input wire:model.live.debounce.250ms="ligne.libelle" placeholder="Rechercher un acte (libellé ou code)…" class="field" aria-label="Acte"
                                   role="combobox" aria-autocomplete="list" aria-controls="actes-proposes" autocomplete="off"
                                   x-on:focus="ouvert = true" x-on:input="ouvert = true; actif = 0"
                                   x-on:keydown.arrow-down.prevent="actif = Math.min(actif + 1, $refs.liste ? $refs.liste.children.length - 1 : 0)"
                                   x-on:keydown.arrow-up.prevent="actif = Math.max(actif - 1, 0)"
                                   x-on:keydown.enter="if (ouvert && $refs.liste?.children[actif]) { $event.preventDefault(); $refs.liste.children[actif].click() }">
                            @if ($ligne['acte_id'])
                                <p class="mt-1 text-xs text-emerald-700"><i class="ti ti-check" aria-hidden="true"></i> Acte du catalogue · prix de référence modifiable</p>
                            @endif
                            @if ($actesProposes->isNotEmpty())
                                <ul id="actes-proposes" x-ref="liste" x-show="ouvert" role="listbox" class="absolute left-0 z-20 mt-1 max-h-64 w-[min(22rem,85vw)] overflow-y-auto rounded-lg border border-slate-200 bg-white text-sm shadow-lg">
                                    @foreach ($actesProposes as $i => $acte)
                                        <li role="option" wire:key="ap-{{ $acte->id }}" wire:click="choisirActe({{ $acte->id }})" x-on:mouseenter="actif = {{ $i }}"
                                            :class="actif === {{ $i }} ? 'bg-primary-light' : ''" :aria-selected="actif === {{ $i }}"
                                            class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2">
                                            <span>@if ($acte->code)<span class="mr-1 text-xs text-slate-500">{{ $acte->code }}</span>@endif{{ $acte->libelle }}</span>
                                            <span class="whitespace-nowrap text-slate-600">{{ $montant($acte->prixPour($patient->assureur_id)) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <input type="number" step="0.01" min="0.01" wire:model="ligne.quantite" class="field col-span-2 sm:col-span-1" aria-label="Quantité">
                        <input type="number" step="1" min="0" wire:model="ligne.prix_unitaire" class="field col-span-4 sm:col-span-2" aria-label="Prix unitaire en MRU" placeholder="Prix (MRU)">
                    </div>
                    @error('libelle')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('quantite')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('prix_unitaire')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" wire:model="lignePourSelection" class="rounded border-slate-300">
                            {{ $selection ? 'Lier aux dents '.implode(', ', $selection) : 'Lier aux dents sélectionnées (aucune)' }}
                        </label>
                        <button type="submit" class="btn-secondary"><i class="ti ti-plus" aria-hidden="true"></i>Ajouter</button>
                    </div>
                </form>
            @endif

            @if ($plan && $peutModifier)
                @php $actions = ['propose' => ['Proposer au patient', 'btn-secondary'], 'accepte' => ['Patient a accepté', 'btn-primary'], 'brouillon' => ['Revenir en brouillon', 'btn-secondary'], 'termine' => ['Terminer le plan', 'btn-primary'], 'annule' => ['Annuler le plan', 'btn-danger']]; @endphp
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (PlanTraitement::TRANSITIONS[$plan->statut] as $cible)
                        <button type="button" wire:click="changerStatutPlan({{ $plan->id }}, '{{ $cible }}')"
                            {!! $cible === 'annule' ? 'wire:confirm="Annuler ce plan de traitement ?"' : '' !!}
                            class="{{ $actions[$cible][1] }}">{{ $actions[$cible][0] }}</button>
                    @endforeach
                </div>
                @error('plan')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            @endif

            @if ($plansClos->isNotEmpty())
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm text-slate-600">Plans précédents ({{ $plansClos->count() }})</summary>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($plansClos as $p)
                            <li class="flex justify-between"><span>{{ $p->created_at->format('d/m/Y') }} · {{ $p->lignes_count }} soins</span><span class="text-slate-500">{{ PlanTraitement::STATUTS[$p->statut] }}</span></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    </div>

    <div x-show="onglet === 'documents'" x-cloak>
        <livewire:fichiers-patient :patient="$patient" :selection="$selection" />
    </div>

    {{-- Historique --}}
    <section x-show="onglet === 'historique'" x-cloak class="card" aria-labelledby="histo-titre">
        <h2 id="histo-titre" class="font-medium">Historique {{ count($selection) === 1 ? 'de la dent '.$selection[0] : 'du dossier' }}</h2>
        <ul class="mt-2 divide-y divide-slate-100 text-sm">
            @forelse ($historique as $h)
                @php
                    $apres = $h->apres;
                    $resume = collect([
                        $apres['presence'] !== 'presente' ? Odontologie::PRESENCE[$apres['presence']] : null,
                        $apres['restauration'] !== 'aucune' ? Odontologie::RESTAURATION[$apres['restauration']] : null,
                        $apres['endodontie'] !== 'non_traitee' ? Odontologie::ENDODONTIE[$apres['endodontie']] : null,
                        collect($apres['faces'])->reject(fn ($e) => $e === 'sain')->map(fn ($e, $f) => "{$f} ".Odontologie::ETATS_FACE[$e])->implode(', ') ?: null,
                        collect($apres['constatations'])->map(fn ($c) => Odontologie::libelleConstatation($c))->implode(', ') ?: null,
                    ])->filter()->implode(' · ') ?: 'Saine';
                @endphp
                <li class="flex gap-3 py-2">
                    <span class="w-10 font-medium">{{ $h->numero }}</span>
                    <span class="flex-1">{{ $resume }}</span>
                    <span class="whitespace-nowrap text-xs text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }} · Dr {{ $h->auteur?->name ?? '—' }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-600">Aucune modification enregistrée.</li>
            @endforelse
        </ul>
    </section>
</div>
