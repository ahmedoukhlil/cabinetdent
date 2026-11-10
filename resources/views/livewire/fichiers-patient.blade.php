<section class="card space-y-3" aria-labelledby="fichiers-titre">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 id="fichiers-titre" class="font-medium">Radios et documents <span class="text-sm font-normal text-slate-500">· {{ $fichiers->count() }}</span></h2>
        @if ($selection)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="filtrerSelection"> Seulement {{ count($selection) === 1 ? 'la dent '.$selection[0] : 'les dents sélectionnées' }}
            </label>
        @endif
    </div>

    @can('dossier.edit')
        <form wire:submit="ajouter" class="grid grid-cols-6 gap-2 rounded-lg bg-slate-50 p-3">
            <div class="col-span-6 sm:col-span-3">
                <label class="label" for="f-fichier">Fichier (JPG, PNG, WEBP ou PDF, 10 Mo max.)</label>
                <input id="f-fichier" type="file" wire:model="fichier" accept=".jpg,.jpeg,.png,.webp,.pdf" class="field">
                <div wire:loading wire:target="fichier" class="text-xs text-slate-500">Envoi en cours…</div>
                @error('fichier')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="f-cat">Type</label>
                <select id="f-cat" wire:model="categorie" class="field">
                    @foreach ($categories as $cle => $libelle)
                        <option value="{{ $cle }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-3 sm:col-span-2">
                <label class="label" for="f-date">Date de l'examen</label>
                <input id="f-date" type="date" wire:model="dateExamen" max="{{ today()->format('Y-m-d') }}" class="field">
                @error('dateExamen')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 sm:col-span-4">
                <label class="label" for="f-titre">Titre (facultatif)</label>
                <input id="f-titre" wire:model="titre" class="field" placeholder="Panoramique initial, rétro-alvéolaire 36…">
            </div>
            <div class="col-span-6 flex items-end justify-between gap-2 sm:col-span-2">
                @if ($selection)
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" wire:model="lierSelection"> Lier à {{ implode(', ', $selection) }}</label>
                @endif
                <button type="submit" class="btn-primary ml-auto" wire:loading.attr="disabled" wire:target="fichier,ajouter"><i class="ti ti-upload" aria-hidden="true"></i>Ajouter</button>
            </div>
        </form>
    @endcan

    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($fichiers as $f)
            <li wire:key="f-{{ $f->id }}" class="overflow-hidden rounded-lg border border-slate-200 text-sm">
                <a href="{{ route('fichiers.voir', $f) }}" target="_blank" class="block aspect-[4/3] bg-slate-100">
                    @if ($f->estImage())
                        <img src="{{ route('fichiers.voir', $f) }}" alt="{{ $f->titre }}" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full items-center justify-center text-4xl text-slate-400"><i class="ti ti-file-type-pdf" aria-hidden="true"></i></span>
                    @endif
                </a>
                <div class="space-y-0.5 p-2">
                    <p class="truncate font-medium" title="{{ $f->titre }}">{{ $f->titre }}</p>
                    <p class="text-xs text-slate-500">{{ $categories[$f->categorie] ?? $f->categorie }} · {{ $f->date_examen?->format('d/m/Y') }}</p>
                    @if ($f->dents)<p class="text-xs text-slate-500">Dents {{ implode(', ', $f->dents) }}</p>@endif
                    <div class="flex items-center justify-between pt-1 text-xs text-slate-500">
                        <span>{{ $f->taille_lisible }}</span>
                        @can('dossier.edit')
                            @if ($f->ajoute_par === auth()->id() || auth()->user()->estProprietaire())
                                <button type="button" wire:click="supprimer({{ $f->id }})" wire:confirm="Retirer ce fichier du dossier ?" class="hover:text-red-700" aria-label="Retirer {{ $f->titre }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                            @endif
                        @endcan
                    </div>
                </div>
            </li>
        @empty
            <li class="col-span-full text-slate-500">Aucun fichier{{ $filtrerSelection && $selection ? ' pour cette sélection' : '' }}.</li>
        @endforelse
    </ul>
</section>
