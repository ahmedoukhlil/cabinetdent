@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $mo = fn ($o) => number_format($o / 1048576, 1, ',', ' ').' Mo';
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Cabinets</h1>
        <div class="flex gap-2">
            <button type="button" wire:click="verifierEcheances" class="btn-secondary" title="Normalement exécuté chaque nuit"><i class="ti ti-refresh" aria-hidden="true"></i>Appliquer les échéances</button>
            <button type="button" wire:click="$toggle('creation')" class="btn-primary"><i class="ti ti-plus" aria-hidden="true"></i>Nouveau cabinet</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 text-sm">
        @foreach (['tous' => 'Tous'] + collect($statuts)->map(fn ($s) => $s[0])->all() as $cle => $libelle)
            <button type="button" wire:click="$set('filtre', '{{ $cle }}')" @class(['rounded-full border px-3 py-1', 'border-slate-900 bg-slate-900 text-white' => $filtre === $cle, 'border-slate-200 bg-white hover:border-slate-400' => $filtre !== $cle])>
                {{ $libelle }}@if ($cle !== 'tous') · {{ $compteurs[$cle] ?? 0 }}@endif
            </button>
        @endforeach
    </div>

    @if ($creation)
        <form wire:submit="creer" class="card grid grid-cols-6 gap-2">
            <p class="col-span-6 font-medium">Nouveau cabinet <span class="text-sm font-normal text-slate-500">· crée le compte du dentiste propriétaire et démarre l'essai</span></p>
            @foreach ([['nom', 'Nom du cabinet', 'text', 3], ['telephone', 'Téléphone', 'text', 3], ['proprietaire', 'Dentiste propriétaire', 'text', 2], ['email', 'E-mail de connexion', 'email', 2], ['password', 'Mot de passe initial', 'password', 2]] as [$champ, $libelle, $type, $col])
                <div class="col-span-6 sm:col-span-{{ $col }}">
                    <label class="label" for="n-{{ $champ }}">{{ $libelle }}</label>
                    <input id="n-{{ $champ }}" type="{{ $type }}" wire:model="nouveau.{{ $champ }}" class="field" @if ($type === 'password') autocomplete="new-password" @endif>
                    @error('nouveau.'.$champ)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @error($champ)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="col-span-4 sm:col-span-3">
                <label class="label" for="n-plan">Formule</label>
                <select id="n-plan" wire:model="nouveau.plan_id" class="field">
                    <option value="">Choisir…</option>
                    @foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->nom }} · {{ $mru($p->prix_mensuel) }}/mois</option>@endforeach
                </select>
                @error('plan_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="label" for="n-essai">Essai (jours)</label>
                <input id="n-essai" type="number" min="0" max="90" wire:model="nouveau.essai_jours" class="field">
            </div>
            <div class="col-span-6 flex items-end justify-end gap-2 sm:col-span-2">
                <button type="button" wire:click="$set('creation', false)" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">Créer</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        <section class="card overflow-x-auto lg:col-span-3" aria-label="Liste des cabinets">
            <table class="tableau-cartes w-full min-w-[560px] text-sm [&_td]:px-2 [&_th]:px-2">
                <thead class="text-left text-xs text-slate-500"><tr><th class="py-1 font-medium">Cabinet</th><th class="py-1 font-medium">Formule</th><th class="py-1 font-medium">Statut</th><th class="py-1 font-medium">Couvert jusqu'au</th><th class="py-1 text-right font-medium">Comptes</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cabinets as $c)
                        @php $a = $c->abonnement; @endphp
                        <tr wire:key="c-{{ $c->id }}" wire:click="ouvrir({{ $c->id }})" @class(['cursor-pointer hover:bg-slate-50', 'bg-primary-light' => $cabinet === $c->id])>
                            <td class="py-2 font-medium">{{ $c->nom }}@unless ($c->actif) <span class="badge bg-red-100 text-red-800">Bloqué</span>@endunless</td>
                            <td class="py-2">{{ $a?->plan?->nom ?? '—' }}</td>
                            <td class="py-2">@if ($a)<span class="badge {{ $statuts[$a->statut][1] }}">{{ $statuts[$a->statut][0] }}</span>@else<span class="text-xs text-slate-500">Sans abonnement</span>@endif</td>
                            <td class="py-2">{{ $a?->finCouverture()?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2 text-right">{{ $c->utilisateurs_count }}{{ $a?->plan?->max_utilisateurs ? ' / '.$a->plan->max_utilisateurs : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-600">Aucun cabinet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="space-y-3 lg:col-span-2" aria-label="Détail du cabinet">
            @if ($ouvert)
                @php $a = $ouvert->abonnement; @endphp
                <div class="card space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="text-base font-semibold">{{ $ouvert->nom }}</h2>
                            <p class="text-slate-500">{{ $proprietaires->map(fn ($p) => $p->name.' · '.$p->email)->join(', ') ?: 'Aucun propriétaire' }}</p>
                        </div>
                        @if ($a)<span class="badge {{ $statuts[$a->statut][1] }}">{{ $statuts[$a->statut][0] }}</span>@endif
                    </div>
                    @if ($a)
                        <dl class="grid grid-cols-2 gap-x-3 gap-y-1">
                            <dt class="text-slate-500">Formule</dt><dd>{{ $a->plan->nom }} · {{ $mru($a->plan->prix_mensuel) }}/mois</dd>
                            @if ($a->essai_fin)<dt class="text-slate-500">Fin d'essai</dt><dd>{{ $a->essai_fin->format('d/m/Y') }}</dd>@endif
                            @if ($a->periode_fin)<dt class="text-slate-500">Payé jusqu'au</dt><dd>{{ $a->periode_fin->format('d/m/Y') }}</dd>@endif
                            @if ($a->statut === 'impaye')<dt class="text-slate-500">Suspension le</dt><dd class="font-medium text-amber-700">{{ $a->finGrace()?->addDay()->format('d/m/Y') }}</dd>@endif
                            @if ($a->resilie_at)<dt class="text-slate-500">Résilié le</dt><dd>{{ $a->resilie_at->format('d/m/Y') }} · {{ $a->motif_resiliation }}</dd>@endif
                            <dt class="text-slate-500">Stockage</dt><dd>{{ $mo($stockage) }}{{ $a->plan->max_stockage_mo ? ' / '.number_format($a->plan->max_stockage_mo, 0, ',', ' ').' Mo' : '' }}</dd>
                        </dl>
                    @else
                        <p class="text-slate-500">Installation sans abonnement : aucune limite ni échéance.</p>
                    @endif
                    <button type="button" wire:click="basculerActif" wire:confirm="{{ $ouvert->actif ? 'Bloquer l\'accès de tous les utilisateurs de ce cabinet ?' : 'Rétablir l\'accès de ce cabinet ?' }}" class="text-xs text-slate-500 hover:underline">{{ $ouvert->actif ? 'Bloquer manuellement l\'accès' : 'Lever le blocage manuel' }}</button>
                </div>

                @if ($a && $a->statut !== 'resilie')
                    <form wire:submit="enregistrerPaiement" class="card grid grid-cols-4 gap-2 text-sm">
                        <p class="col-span-4 font-medium">Enregistrer un paiement</p>
                        <div class="col-span-1"><label class="label" for="p-mois">Mois</label><input id="p-mois" type="number" min="1" max="36" wire:model="paiement.mois" class="field"></div>
                        <div class="col-span-3"><label class="label" for="p-mt">Montant (vide = {{ $mru($a->plan->prix_mensuel) }} × mois)</label><input id="p-mt" type="number" min="0" step="any" wire:model="paiement.montant" class="field"></div>
                        <div class="col-span-2"><label class="label" for="p-mode">Mode</label><select id="p-mode" wire:model="paiement.mode" class="field">@foreach ($modes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div class="col-span-2"><label class="label" for="p-ref">Référence</label><input id="p-ref" wire:model="paiement.reference" class="field"></div>
                        @error('paiement.mois')<p class="col-span-4 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="col-span-4 text-xs text-slate-500">La nouvelle période part de la date la plus tardive entre aujourd'hui, la fin de période et la fin d'essai.</p>
                        <div class="col-span-4 flex justify-end"><button type="submit" class="btn-primary">Enregistrer</button></div>
                    </form>

                    <div class="card space-y-2 text-sm">
                        <div class="flex items-end gap-2">
                            <div class="grow"><label class="label" for="c-plan">Changer de formule</label><select id="c-plan" wire:model="planId" class="field">@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->nom }}</option>@endforeach</select></div>
                            <button type="button" wire:click="changerPlan" class="btn-secondary">Appliquer</button>
                        </div>
                        <div class="flex items-end gap-2 border-t border-slate-100 pt-2">
                            <div class="grow"><label class="label" for="r-motif">Résilier (définitif)</label><input id="r-motif" wire:model="motif" class="field" placeholder="Motif"></div>
                            <button type="button" wire:click="resilier" wire:confirm="La résiliation est définitive. Continuer ?" class="btn-danger">Résilier</button>
                        </div>
                        @error('motif')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="card flex items-end gap-2 text-sm">
                        <div class="grow"><label class="label" for="na-plan">{{ $a ? 'Nouvel abonnement' : 'Créer un abonnement' }}</label><select id="na-plan" wire:model="planId" class="field"><option value="">Formule…</option>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->nom }}</option>@endforeach</select></div>
                        <button type="button" wire:click="nouvelAbonnement" class="btn-primary">Créer</button>
                    </div>
                @endif

                @if ($historique->isNotEmpty())
                    <div class="card text-sm">
                        <h3 class="mb-2 font-medium">Paiements</h3>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($historique as $p)
                                <li class="flex justify-between gap-2 py-1.5"><span>{{ $p->created_at->format('d/m/Y') }} · {{ $p->mois }} mois · {{ $modes[$p->mode] ?? $p->mode }}{{ $p->reference ? ' · '.$p->reference : '' }}</span><span class="font-medium">{{ $mru($p->montant) }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="card text-sm text-slate-600">Sélectionnez un cabinet pour voir son abonnement.</div>
            @endif
        </section>
    </div>
</div>
