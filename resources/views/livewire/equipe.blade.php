<div class="space-y-4">
    <h1 class="text-lg font-semibold">Équipe et cabinet</h1>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="card space-y-3 lg:col-span-2" aria-labelledby="equipe-titre">
            <h2 id="equipe-titre" class="font-medium">Comptes</h2>

            <form wire:submit="enregistrer" class="grid grid-cols-6 gap-2 rounded-lg bg-slate-50 p-3">
                <p class="col-span-6 text-sm font-medium">{{ $compte['id'] ? 'Modifier le compte' : 'Nouveau compte' }}</p>
                <div class="col-span-6 sm:col-span-3">
                    <label class="label" for="c-nom">Nom</label>
                    <input id="c-nom" wire:model="compte.name" class="field" placeholder="Dr Aminata Sy">
                    @error('compte.name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="label" for="c-email">E-mail de connexion</label>
                    <input id="c-email" type="email" wire:model="compte.email" class="field">
                    @error('compte.email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="label" for="c-role">Rôle</label>
                    <select id="c-role" wire:model="compte.role" class="field">
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('compte.role')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="label" for="c-mdp">{{ $compte['id'] ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe initial' }}</label>
                    <input id="c-mdp" type="password" wire:model="compte.password" class="field" autocomplete="new-password" placeholder="8 caractères minimum">
                    @error('compte.password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-6 flex justify-end gap-2">
                    @if ($compte['id'])<button type="button" wire:click="nouveau" class="btn-secondary">Annuler</button>@endif
                    <button type="submit" class="btn-primary">{{ $compte['id'] ? 'Mettre à jour' : 'Créer le compte' }}</button>
                </div>
            </form>

            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($membres as $m)
                    <li wire:key="u-{{ $m->id }}" @class(['flex flex-wrap items-center gap-2 py-2', 'opacity-50' => !$m->actif])>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $m->name }}@if ($m->id === auth()->id()) <span class="text-xs font-normal text-slate-500">(vous)</span>@endif</p>
                            <p class="truncate text-slate-500">{{ $m->email }}</p>
                        </div>
                        <span class="badge bg-slate-100 text-slate-700">{{ $m->role->label() }}</span>
                        @unless ($m->actif)<span class="badge bg-red-100 text-red-800">Désactivé</span>@endunless
                        <button type="button" wire:click="modifier({{ $m->id }})" class="text-slate-500 hover:text-primary" aria-label="Modifier {{ $m->name }}"><i class="ti ti-edit" aria-hidden="true"></i></button>
                        @if ($m->id !== auth()->id())
                            <button type="button" wire:click="basculerActif({{ $m->id }})" class="text-xs text-slate-500 hover:underline">{{ $m->actif ? 'Désactiver' : 'Réactiver' }}</button>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-slate-500">Un compte n'est jamais supprimé : il reste l'auteur de ses rendez-vous, soins et paiements. Un compte désactivé ne peut plus se connecter.</p>
        </section>

        <section class="card space-y-3" aria-labelledby="cabinet-titre">
            <h2 id="cabinet-titre" class="font-medium">Cabinet</h2>
            <form wire:submit="enregistrerCabinet" class="space-y-2">
                <div>
                    <label class="label" for="cab-nom">Nom</label>
                    <input id="cab-nom" wire:model="cabinet.nom" class="field">
                    @error('cabinet.nom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="cab-tel">Téléphone</label>
                    <input id="cab-tel" wire:model="cabinet.telephone" class="field">
                </div>
                <div>
                    <label class="label" for="cab-adr">Adresse</label>
                    <input id="cab-adr" wire:model="cabinet.adresse" class="field">
                </div>
                <div>
                    <label class="label" for="cab-duree">Durée d'un rendez-vous (minutes)</label>
                    <input id="cab-duree" type="number" min="5" max="240" wire:model="cabinet.duree_rdv_minutes" class="field">
                    @error('cabinet.duree_rdv_minutes')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label" for="cab-ouv">Ouverture</label><input id="cab-ouv" type="time" wire:model="cabinet.heure_ouverture" class="field"></div>
                    <div><label class="label" for="cab-ferm">Fermeture</label><input id="cab-ferm" type="time" wire:model="cabinet.heure_fermeture" class="field"></div>
                </div>
                @error('cabinet.heure_fermeture')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <fieldset>
                    <legend class="label">Jours d'ouverture</legend>
                    <div class="flex flex-wrap gap-2 text-sm">
                        @foreach (\App\Models\Cabinet::JOURS as $n => $j)
                            <label class="flex items-center gap-1"><input type="checkbox" value="{{ $n }}" wire:model="cabinet.jours_ouverture"> {{ $j }}</label>
                        @endforeach
                    </div>
                    @error('cabinet.jours_ouverture')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>
                <p class="text-xs text-slate-500">Le nom, le téléphone et l'adresse figurent en tête des factures et reçus.</p>
                <div class="flex justify-end"><button type="submit" class="btn-primary">Enregistrer</button></div>
            </form>
            <div class="border-t border-slate-100 pt-3">
                <h3 class="text-sm font-medium">Sauvegarde</h3>
                <p class="mb-2 text-xs text-slate-500">Archive ZIP de toutes les données du cabinet (JSON) et des radios et documents. Les mots de passe ne sont pas inclus. Conservez-la en lieu sûr : elle contient des données médicales.</p>
                <a href="{{ route('cabinet.export') }}" class="btn-secondary"><i class="ti ti-download" aria-hidden="true"></i>Exporter le cabinet</a>
            </div>
        </section>
    </div>
</div>
