<div>
@if ($ouvert)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="pr-titre"
         data-patient-rapide x-data x-on:keydown.escape.window="$wire.fermer()" x-init="$nextTick(() => $el.querySelector('[data-focus]')?.focus())">
        <form wire:submit="creer" class="max-h-[90vh] w-full max-w-md space-y-3 overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 id="pr-titre" class="font-semibold">Nouveau patient</h2>
                    <p class="text-sm text-slate-600">Aucun patient trouvé : créez sa fiche, vous la compléterez plus tard.</p>
                </div>
                <button type="button" wire:click="fermer" class="text-slate-400 hover:text-slate-700" aria-label="Fermer"><i class="ti ti-x" aria-hidden="true"></i></button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="pr-nom">Nom *</label>
                    <input id="pr-nom" wire:model="form.nom" class="field" autocomplete="off" @if ($form['nom'] === '') data-focus @endif>
                    @error('form.nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="pr-prenom">Prénom</label>
                    <input id="pr-prenom" wire:model="form.prenom" class="field" autocomplete="off" @if ($form['nom'] !== '' && $form['prenom'] === '') data-focus @endif>
                </div>
                <div class="col-span-2">
                    <label class="label" for="pr-tel">Téléphone</label>
                    <input id="pr-tel" type="tel" wire:model="form.telephone" class="field" autocomplete="off">
                    @error('form.telephone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="pr-sexe">Sexe</label>
                    <select id="pr-sexe" wire:model="form.sexe" class="field"><option value="">—</option><option value="F">Femme</option><option value="M">Homme</option></select>
                </div>
                <div>
                    <label class="label" for="pr-ddn">Naissance</label>
                    <input id="pr-ddn" type="date" wire:model="form.date_naissance" class="field" max="{{ today()->format('Y-m-d') }}">
                    @error('form.date_naissance')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" wire:click="fermer" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled"><i class="ti ti-user-plus" aria-hidden="true"></i>Créer le patient</button>
            </div>
        </form>
    </div>
@endif
</div>
