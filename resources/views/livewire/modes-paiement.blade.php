<div class="space-y-4">
    <div>
        <h1 class="text-lg font-semibold">Modes de paiement</h1>
        <p class="text-sm text-slate-600">Proposés à l'encaissement, au remboursement et pour les dépenses, dans l'ordre de cette liste.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <form wire:submit="enregistrer" class="card space-y-3 self-start" aria-labelledby="mp-form-titre">
            <h2 id="mp-form-titre" class="font-medium">{{ $form['id'] ? 'Modifier le mode' : 'Nouveau mode' }}</h2>
            <div>
                <label class="label" for="mp-libelle">Libellé</label>
                <input id="mp-libelle" wire:model="form.libelle" placeholder="Bankily, Masrvi, Sedad…" class="field" maxlength="50" autocomplete="off">
                @error('form.libelle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="form.actif" class="rounded border-slate-300"> Actif (proposé à la saisie)
            </label>
            @error('form.actif')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-2">
                @if ($form['id'])<button type="button" wire:click="nouveau" class="btn-secondary">Annuler</button>@endif
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ $form['id'] ? 'Mettre à jour' : 'Ajouter' }}</button>
            </div>
            <p class="text-xs text-slate-500">Un mode déjà utilisé ne peut pas être supprimé, seulement désactivé : l'historique de la caisse garde son libellé.</p>
        </form>

        <section class="card overflow-x-auto !p-0 lg:col-span-2" aria-label="Modes de paiement">
            <table class="tableau-cartes w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-500">
                    <tr><th class="px-3 py-2 font-medium">Mode</th><th class="px-3 py-2 font-medium">Statut</th><th class="px-3 py-2 text-right font-medium">Utilisations</th><th class="px-3 py-2"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($modes as $m)
                        <tr wire:key="mp-{{ $m->id }}" @class(['hover:bg-slate-50', 'bg-primary-light/40' => $form['id'] === $m->id, 'opacity-60' => !$m->actif])>
                            <td class="px-3 py-2 font-medium">{{ $m->libelle }}</td>
                            <td class="px-3 py-2">
                                @if ($m->actif)<span class="badge bg-emerald-50 text-emerald-700">Actif</span>@else<span class="badge bg-slate-100 text-slate-600">Désactivé</span>@endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $utilisations[$m->id] }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="deplacer({{ $m->id }}, -1)" @disabled($loop->first) class="rounded p-1 text-slate-500 hover:bg-slate-100 disabled:opacity-30" aria-label="Monter {{ $m->libelle }}" title="Monter"><i class="ti ti-arrow-up" aria-hidden="true"></i></button>
                                <button type="button" wire:click="deplacer({{ $m->id }}, 1)" @disabled($loop->last) class="rounded p-1 text-slate-500 hover:bg-slate-100 disabled:opacity-30" aria-label="Descendre {{ $m->libelle }}" title="Descendre"><i class="ti ti-arrow-down" aria-hidden="true"></i></button>
                                <button type="button" wire:click="modifier({{ $m->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-primary" aria-label="Modifier {{ $m->libelle }}" title="Modifier"><i class="ti ti-edit" aria-hidden="true"></i></button>
                                <button type="button" wire:click="basculer({{ $m->id }})" class="rounded p-1 text-slate-500 hover:bg-slate-100" aria-label="{{ $m->actif ? 'Désactiver' : 'Réactiver' }} {{ $m->libelle }}" title="{{ $m->actif ? 'Désactiver' : 'Réactiver' }}"><i class="ti {{ $m->actif ? 'ti-eye-off' : 'ti-eye' }}" aria-hidden="true"></i></button>
                                @if ($utilisations[$m->id] === 0)
                                    <button type="button" wire:click="supprimer({{ $m->id }})" wire:confirm="Supprimer le mode « {{ $m->libelle }} » ?" class="rounded p-1 text-slate-500 hover:bg-red-50 hover:text-red-700" aria-label="Supprimer {{ $m->libelle }}" title="Supprimer"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-10 text-center text-slate-600">Aucun mode de paiement. Ajoutez-en un.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
