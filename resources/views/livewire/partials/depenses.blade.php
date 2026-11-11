<section class="card space-y-3" aria-labelledby="depenses-titre">
    <h2 id="depenses-titre" class="font-medium">Dépenses <span class="text-sm font-normal text-slate-500">· {{ $mru($totaux['depenses']) }} sur la période</span></h2>

    @can('depense.create')
        <form wire:submit="enregistrerDepense" class="grid grid-cols-6 gap-2 rounded-lg bg-slate-50 p-3">
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="dep-date">Date</label>
                <input id="dep-date" type="date" wire:model="depense.date_depense" max="{{ today()->format('Y-m-d') }}" class="field">
            </div>
            <div class="col-span-3 sm:col-span-2">
                <label class="label" for="dep-cat">Catégorie</label>
                <select id="dep-cat" wire:model="depense.categorie" class="field">
                    @foreach ($categories as $cle => $libelle)<option value="{{ $cle }}">{{ $libelle }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <label class="label" for="dep-lib">Libellé</label>
                <input id="dep-lib" wire:model="depense.libelle" class="field" placeholder="Couronne céramique patient Ba, gants…">
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="dep-mt">Montant (MRU)</label>
                <input id="dep-mt" type="number" min="1" step="1" wire:model="depense.montant" class="field">
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="dep-mode">Mode</label>
                <select id="dep-mode" wire:model="depense.mode" class="field">
                    @foreach ($modesActifs as $cle => $libelle)<option value="{{ $cle }}">{{ $libelle }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-3 sm:col-span-2">
                <label class="label" for="dep-dent">Praticien concerné</label>
                <select id="dep-dent" wire:model="depense.dentiste_id" class="field">
                    <option value="">Frais du cabinet</option>
                    @foreach ($dentistes as $d)<option value="{{ $d->id }}">Dr {{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="dep-ref">Référence</label>
                <input id="dep-ref" wire:model="depense.reference" class="field" placeholder="N° reçu">
            </div>
            <div class="col-span-6 flex items-end justify-end sm:col-span-1">
                <button type="submit" class="btn-primary w-full">Enregistrer</button>
            </div>
            @foreach (['date_depense', 'libelle', 'montant', 'dentiste_id'] as $champ)
                @error('depense.'.$champ)<p class="col-span-6 text-xs text-red-600">{{ $message }}</p>@enderror
            @endforeach
        </form>
    @endcan

    <ul class="divide-y divide-slate-100 text-sm">
        @forelse ($depenses as $dep)
            <li wire:key="dep-{{ $dep->id }}" class="py-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="w-12 text-slate-500">{{ $dep->date_depense->format('d/m') }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ $dep->libelle }}</p>
                        <p class="text-xs text-slate-500">{{ $categories[$dep->categorie] ?? $dep->categorie }} · {{ $modes[$dep->mode] ?? $dep->mode }}{{ $dep->reference ? ' · réf. '.$dep->reference : '' }} · {{ $dep->dentiste ? 'Dr '.$dep->dentiste->name : 'Cabinet' }} · saisie par {{ $dep->auteur?->name }}</p>
                    </div>
                    <span class="font-medium text-red-700">−{{ $mru($dep->montant) }}</span>
                    @can('depense.delete')
                        <button type="button" wire:click="$set('suppression', {{ $dep->id }})" class="text-slate-400 hover:text-red-700" aria-label="Supprimer {{ $dep->libelle }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                    @endcan
                </div>
                @if ($suppression === $dep->id)
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                        <div class="grow">
                            <label class="label" for="dep-motif">Motif de suppression</label>
                            <input id="dep-motif" wire:model="motif" class="field">
                            @error('motif')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" wire:click="supprimerDepense" class="btn-danger">Confirmer</button>
                        <button type="button" wire:click="$set('suppression', null)" class="btn-secondary">Retour</button>
                    </div>
                @endif
            </li>
        @empty
            <li class="py-4 text-center text-slate-600">Aucune dépense sur la période.</li>
        @endforelse
    </ul>
</section>
