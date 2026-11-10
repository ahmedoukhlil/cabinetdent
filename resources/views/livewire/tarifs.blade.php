@php $mru = fn ($v) => number_format((float) $v, 0, ',', ' '); @endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Tarifs et assurances</h1>
            <p class="text-sm text-slate-500">{{ $nbActes }} acte{{ $nbActes > 1 ? 's' : '' }} actif{{ $nbActes > 1 ? 's' : '' }} · {{ $assureursActifs->count() }} assurance{{ $assureursActifs->count() > 1 ? 's' : '' }} · prix en MRU</p>
        </div>
        <div role="tablist" aria-label="Rubriques" class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
            <button type="button" role="tab" aria-selected="{{ $onglet === 'actes' ? 'true' : 'false' }}" wire:click="$set('onglet', 'actes')"
                @class(['rounded-md px-3 py-1.5', 'bg-primary text-white' => $onglet === 'actes', 'text-slate-600 hover:bg-slate-50' => $onglet !== 'actes'])>
                <i class="ti ti-list-details" aria-hidden="true"></i> Actes
            </button>
            <button type="button" role="tab" aria-selected="{{ $onglet === 'assurances' ? 'true' : 'false' }}" wire:click="$set('onglet', 'assurances')"
                @class(['rounded-md px-3 py-1.5', 'bg-primary text-white' => $onglet === 'assurances', 'text-slate-600 hover:bg-slate-50' => $onglet !== 'assurances'])>
                <i class="ti ti-shield-check" aria-hidden="true"></i> Assurances
            </button>
        </div>
    </div>

    @if ($onglet === 'actes')
        {{-- Barre d'outils --}}
        <div class="card flex flex-wrap items-center gap-2 !p-3">
            <div class="relative min-w-48 flex-1">
                <i class="ti ti-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="Rechercher un acte ou un code…" class="field !pl-9" aria-label="Rechercher un acte">
            </div>
            <label class="flex items-center gap-2 px-1 text-sm text-slate-600"><input type="checkbox" wire:model.live="inactifs" class="rounded border-slate-300"> Afficher les inactifs</label>
            <a href="{{ route('tarifs.modele') }}" class="btn-secondary !py-2"><i class="ti ti-file-spreadsheet" aria-hidden="true"></i> Modèle Excel</a>
            <button type="button" wire:click="ouvrirImport" @class(['btn-secondary !py-2', '!border-primary !text-primary' => $import])><i class="ti ti-upload" aria-hidden="true"></i> Importer</button>
            <button type="button" wire:click="nouvelActe" class="btn-primary !py-2"><i class="ti ti-plus" aria-hidden="true"></i> Nouvel acte</button>
        </div>

        {{-- Import groupé --}}
        @if ($import)
            <section class="card space-y-3 border-primary/30" aria-labelledby="import-titre">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 id="import-titre" class="font-medium">Import groupé depuis Excel</h2>
                        <p class="text-sm text-slate-600">Téléchargez le modèle : il contient votre catalogue actuel et une colonne de prix par assurance active. Modifiez-le, ajoutez des lignes, puis importez-le.</p>
                    </div>
                    <button type="button" wire:click="ouvrirImport" class="text-slate-400 hover:text-slate-700" aria-label="Fermer l'import"><i class="ti ti-x" aria-hidden="true"></i></button>
                </div>
                <ol class="grid gap-2 text-sm sm:grid-cols-3">
                    <li class="rounded-lg bg-slate-50 p-3"><span class="font-medium text-primary">1.</span> <a href="{{ route('tarifs.modele') }}" class="text-primary underline">Télécharger le modèle</a> (.xlsx)</li>
                    <li class="rounded-lg bg-slate-50 p-3"><span class="font-medium text-primary">2.</span> Une ligne par acte. Case assureur vide = prix sans assurance.</li>
                    <li class="rounded-lg bg-slate-50 p-3"><span class="font-medium text-primary">3.</span> Importer : tout ou rien, les erreurs sont listées par ligne.</li>
                </ol>
                <form wire:submit="importer" class="flex flex-wrap items-center gap-2">
                    <input type="file" wire:model="fichier" accept=".xlsx" class="field max-w-md flex-1 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm" aria-label="Fichier Excel des actes">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="fichier,importer">
                        <span wire:loading.remove wire:target="importer"><i class="ti ti-file-import" aria-hidden="true"></i> Importer</span>
                        <span wire:loading wire:target="importer">Import en cours…</span>
                    </button>
                </form>
                @error('fichier')
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                        <p class="font-medium"><i class="ti ti-alert-triangle" aria-hidden="true"></i> Rien n'a été importé.</p>
                        <ul class="mt-1 list-disc pl-5">@foreach ($errors->get('fichier') as $m)<li>{{ $m }}</li>@endforeach</ul>
                    </div>
                @enderror
                @if ($rapport)
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900" role="status">
                        <p class="font-medium"><i class="ti ti-circle-check" aria-hidden="true"></i> Import réussi : {{ $rapport['crees'] }} acte(s) créé(s), {{ $rapport['modifies'] }} modifié(s), {{ $rapport['tarifs'] }} prix assureur enregistré(s).</p>
                        @if ($rapport['avertissements'])<ul class="mt-1 list-disc pl-5 text-amber-800">@foreach ($rapport['avertissements'] as $m)<li>{{ $m }}</li>@endforeach</ul>@endif
                    </div>
                @endif
            </section>
        @endif

        {{-- Création / modification --}}
        @if ($edition)
            <form wire:submit="enregistrerActe" class="card space-y-3 border-primary/30" aria-labelledby="edition-titre">
                <h2 id="edition-titre" class="font-medium">{{ $acte['id'] ? 'Modifier l\'acte' : 'Nouvel acte' }}</h2>
                <div class="grid grid-cols-6 gap-3">
                    <div class="col-span-2 sm:col-span-1"><label class="label" for="a-code">Code</label><input id="a-code" wire:model="acte.code" class="field" placeholder="D01"></div>
                    <div class="col-span-4 sm:col-span-2"><label class="label" for="a-lib">Libellé</label><input id="a-lib" wire:model="acte.libelle" class="field" placeholder="Détartrage">@error('acte.libelle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="col-span-3 sm:col-span-1"><label class="label" for="a-type">Type</label><select id="a-type" wire:model="acte.type" class="field">@foreach (\App\Models\Acte::TYPES as $cle => $lib)<option value="{{ $cle }}">{{ $lib }}</option>@endforeach</select></div>
                    <div class="col-span-3 sm:col-span-2"><label class="label" for="a-prix">Prix sans assurance</label><input id="a-prix" type="number" step="any" min="0" wire:model="acte.prix" class="field" placeholder="0">@error('acte.prix')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                @if ($assureursActifs->isNotEmpty())
                    <fieldset class="rounded-lg bg-slate-50 p-3">
                        <legend class="px-1 text-sm font-medium text-slate-700">Prix par assurance <span class="font-normal text-slate-500">· vide = prix sans assurance</span></legend>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($assureursActifs as $as)
                                <div wire:key="t-{{ $as->id }}">
                                    <label class="label !text-xs" for="t-{{ $as->id }}">{{ $as->nom }} <span class="font-normal text-slate-500">({{ $as->pourcentage }})</span></label>
                                    <input id="t-{{ $as->id }}" type="number" step="any" min="0" wire:model="acte.tarifs.{{ $as->id }}" class="field" placeholder="{{ $acte['prix'] !== '' ? $mru($acte['prix']) : '—' }}">
                                    @error('acte.tarifs.'.$as->id)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="annulerActe" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">{{ $acte['id'] ? 'Mettre à jour' : 'Ajouter' }}</button>
                </div>
            </form>
        @endif

        {{-- Catalogue --}}
        <section class="card overflow-x-auto !p-0" aria-label="Catalogue des actes">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-slate-50 text-left text-xs text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-medium">Code</th>
                        <th class="px-3 py-2 font-medium">Acte</th>
                        <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Sans assurance</th>
                        @foreach ($assureursActifs as $as)
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap" title="Prise en charge {{ $as->pourcentage }}">{{ $as->nom }}</th>
                        @endforeach
                        <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($actes as $a)
                        <tr wire:key="acte-{{ $a->id }}" @class(['hover:bg-slate-50', 'opacity-50' => !$a->actif, 'bg-primary/5' => $edition && $acte['id'] === $a->id])>
                            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $a->code ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $a->libelle }}@if ($a->estConsultation()) <span class="badge bg-sky-50 text-sky-800" title="Facturée et encaissée depuis la salle d'attente">consultation</span>@endif @if (!$a->actif) <span class="badge bg-slate-100 text-slate-600">inactif</span>@endif</td>
                            <td class="px-3 py-2 text-right font-medium tabular-nums">{{ $mru($a->prix) }}</td>
                            @foreach ($assureursActifs as $as)
                                @php $t = $a->tarifs->firstWhere('assureur_id', $as->id); @endphp
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($t)
                                        <span title="Prix propre à {{ $as->nom }}">{{ $mru($t->prix) }}</span>
                                    @else
                                        <span class="text-slate-400" title="Prix sans assurance appliqué">{{ $mru($a->prix) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="modifierActe({{ $a->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-primary" aria-label="Modifier {{ $a->libelle }}" title="Modifier"><i class="ti ti-edit" aria-hidden="true"></i></button>
                                <button type="button" wire:click="basculerActe({{ $a->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100" aria-label="{{ $a->actif ? 'Désactiver' : 'Réactiver' }} {{ $a->libelle }}" title="{{ $a->actif ? 'Désactiver' : 'Réactiver' }}"><i class="ti {{ $a->actif ? 'ti-eye-off' : 'ti-eye' }}" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 4 + $assureursActifs->count() }}" class="px-3 py-10 text-center text-slate-600">
                            @if ($recherche !== '')
                                Aucun acte ne correspond à « {{ $recherche }} ».
                            @else
                                <i class="ti ti-list-details mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                                Aucun acte. Ajoutez-les un par un ou importez votre catalogue depuis Excel.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        @if ($assureursActifs->isNotEmpty())
            <p class="text-xs text-slate-500">Prix en gris : pas de tarif propre à l'assurance, le prix sans assurance s'applique. Le prix est repris selon l'assurance du patient dans le plan de traitement et la facture.</p>
        @endif
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <form wire:submit="enregistrerAssureur" class="card space-y-3 self-start" aria-labelledby="ass-form-titre">
                <h2 id="ass-form-titre" class="font-medium">{{ $assureur['id'] ? 'Modifier l\'assurance' : 'Nouvelle assurance' }}</h2>
                <div><label class="label" for="as-nom">Nom</label><input id="as-nom" wire:model="assureur.nom" placeholder="CNAM, mutuelle…" class="field">@error('assureur.nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div>
                    <label class="label" for="as-taux">Taux de prise en charge</label>
                    <div class="relative"><input id="as-taux" type="number" step="0.01" min="0" max="100" wire:model="assureur.taux" placeholder="80" class="field !pr-8"><span class="absolute top-1/2 right-3 -translate-y-1/2 text-sm text-slate-500">%</span></div>
                    @error('assureur.taux')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2">
                    @if ($assureur['id'])<button type="button" wire:click="$set('assureur', {id: null, nom: '', taux: ''})" class="btn-secondary">Annuler</button>@endif
                    <button type="submit" class="btn-primary">{{ $assureur['id'] ? 'Mettre à jour' : 'Ajouter' }}</button>
                </div>
                <p class="text-xs text-slate-500">Le taux s'applique aux nouvelles factures. Une facture validée garde le taux en vigueur à sa validation.</p>
            </form>

            <section class="card overflow-x-auto !p-0 lg:col-span-2" aria-label="Assurances">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                        <tr><th class="px-3 py-2 font-medium">Assurance</th><th class="px-3 py-2 text-right font-medium">Prise en charge</th><th class="px-3 py-2 text-right font-medium">Tarifs propres</th><th class="px-3 py-2 text-right font-medium">Patients</th><th class="px-3 py-2"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assureurs as $a)
                            <tr wire:key="ass-{{ $a->id }}" @class(['hover:bg-slate-50', 'opacity-50' => !$a->actif])>
                                <td class="px-3 py-2 font-medium">{{ $a->nom }}@if (!$a->actif) <span class="badge bg-slate-100 text-slate-600">inactive</span>@endif</td>
                                <td class="px-3 py-2 text-right"><span class="badge bg-sky-50 text-sky-800">{{ $a->pourcentage }}</span></td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $tarifsParAssureur[$a->id] ?? 0 }} acte(s)</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $a->patients_count }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <button type="button" wire:click="modifierAssureur({{ $a->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-primary" aria-label="Modifier {{ $a->nom }}" title="Modifier"><i class="ti ti-edit" aria-hidden="true"></i></button>
                                    <button type="button" wire:click="basculerAssureur({{ $a->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100" aria-label="{{ $a->actif ? 'Désactiver' : 'Réactiver' }} {{ $a->nom }}" title="{{ $a->actif ? 'Désactiver' : 'Réactiver' }}"><i class="ti {{ $a->actif ? 'ti-eye-off' : 'ti-eye' }}" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-10 text-center text-slate-600">Aucune assurance enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    @endif
</div>
