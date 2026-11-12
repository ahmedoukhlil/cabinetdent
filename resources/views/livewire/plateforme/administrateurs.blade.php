<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Administrateurs</h1>
            <p class="text-sm text-slate-500">Comptes d'accès à cet espace plateforme. Tous ont les mêmes droits.</p>
        </div>
        @unless ($edition)
            <button type="button" wire:click="nouveau" class="btn-primary w-full whitespace-nowrap sm:w-auto"><i class="ti ti-user-plus" aria-hidden="true"></i>Nouvel administrateur</button>
        @endunless
    </div>

    @if ($edition)
        <form wire:submit="enregistrer" class="card grid grid-cols-6 items-end gap-2">
            <p class="col-span-6 font-medium">{{ $form['id'] ? 'Modifier '.$form['name'] : 'Nouvel administrateur' }}</p>
            <div class="col-span-6 sm:col-span-2">
                <label class="label" for="a-nom">Nom</label>
                <input id="a-nom" wire:model="form.name" class="field" autocomplete="off">
                @error('form.name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="label" for="a-email">E-mail de connexion</label>
                <input id="a-email" type="email" wire:model="form.email" class="field" autocomplete="off">
                @error('form.email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="label" for="a-mdp">{{ $form['id'] ? 'Nouveau mot de passe' : 'Mot de passe' }}</label>
                <input id="a-mdp" type="password" wire:model="form.password" class="field" autocomplete="new-password"
                       placeholder="{{ $form['id'] ? 'Laisser vide pour ne pas changer' : $min.' caractères minimum' }}">
                @error('form.password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 flex justify-end gap-2">
                <button type="button" wire:click="annuler" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">{{ $form['id'] ? 'Enregistrer' : 'Créer' }}</button>
            </div>
        </form>
    @endif

    @error('liste')<p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror

    <section class="card" aria-label="Liste des administrateurs">
        <ul class="-my-1 divide-y divide-slate-100">
            @foreach ($admins as $a)
                <li wire:key="adm-{{ $a->id }}" @class(['flex flex-wrap items-center gap-x-3 gap-y-2 py-3 text-sm', 'opacity-60' => !$a->actif])>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->name, 0, 1)) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">
                            {{ $a->name }}
                            @if ($a->id === $moiId)<span class="badge bg-primary/10 text-primary">Vous</span>@endif
                            @unless ($a->actif)<span class="badge bg-slate-200 text-slate-700">Désactivé</span>@endunless
                        </p>
                        <p class="break-all text-xs text-slate-500">{{ $a->email }}</p>
                        <p class="text-xs text-slate-500">Dernière connexion : {{ $a->derniere_connexion_at?->format('d/m/Y à H:i') ?? 'jamais' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" wire:click="modifier({{ $a->id }})" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-primary" aria-label="Modifier {{ $a->name }}" title="Modifier"><i class="ti ti-edit text-lg" aria-hidden="true"></i></button>
                        @if ($a->id !== $moiId)
                            <button type="button" wire:click="basculer({{ $a->id }})"
                                    wire:confirm="{{ $a->actif ? 'Désactiver '.$a->name.' ? Ses sessions ouvertes seront fermées.' : 'Réactiver '.$a->name.' ?' }}"
                                    @class(['rounded-lg px-2 py-1.5 text-xs font-medium', 'text-red-700 hover:bg-red-50' => $a->actif, 'text-emerald-700 hover:bg-emerald-50' => !$a->actif])>
                                {{ $a->actif ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</div>
